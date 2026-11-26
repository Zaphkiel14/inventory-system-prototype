<?php

namespace App\Services\Inventory;

use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Decides which batches a release is drawn from.
 *
 *  - Items that require an expiry date use FEFO: the batch expiring soonest
 *    goes first (expired batches are never issued).
 *  - Everything else uses FIFO: the batch received first goes first.
 */
class BatchAllocator
{
    public static function methodFor(InventoryItems $item): string
    {
        return $item->requires_expiry ? 'FEFO' : 'FIFO';
    }

    /**
     * Usable batches for the item in the order they should be drawn from.
     *
     * @return Builder<InventoryBatches>
     */
    public function queue(InventoryItems $item): Builder
    {
        return $this->drawOrder(
            InventoryBatches::query()->where('inventory_item_id', $item->id),
            (bool) $item->requires_expiry,
        );
    }

    /**
     * The queue() of several items, loaded in at most two queries (one per
     * method) instead of one per item. Keyed by item id; every requested item
     * is present, with an empty collection when it has no usable batches.
     *
     * @param  Collection<int, InventoryItems>  $items
     * @return Collection<int, Collection<int, InventoryBatches>>
     */
    public function queueFor(Collection $items): Collection
    {
        [$fefo, $fifo] = $items->partition(fn (InventoryItems $item) => (bool) $item->requires_expiry);

        // Each query returns batches already in draw order; filtering per item
        // keeps that order.
        $batches = $this->drawOrderFor($fefo, true)->concat($this->drawOrderFor($fifo, false));

        return $items->mapWithKeys(fn (InventoryItems $item) => [
            $item->id => $batches->where('inventory_item_id', $item->id)->values()->toBase(),
        ]);
    }

    /**
     * Usable batches of the given items in draw order (no query when empty).
     *
     * @param  Collection<int, InventoryItems>  $items
     * @return EloquentCollection<int, InventoryBatches>
     */
    protected function drawOrderFor(Collection $items, bool $fefo): EloquentCollection
    {
        if ($items->isEmpty()) {
            return new EloquentCollection;
        }

        return $this->drawOrder(
            InventoryBatches::query()->whereIn('inventory_item_id', $items->pluck('id')->unique()->values()),
            $fefo,
        )->get();
    }

    /**
     * Plan how to draw $quantity from the item's batches. When $lock is true
     * the batches are locked for update (use inside a transaction).
     *
     * Pass the same $taken map for every line of one release: see allocateFrom().
     *
     * @param  array<int, int>  $taken
     * @return array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}
     */
    public function allocate(InventoryItems $item, int $quantity, bool $lock = false, array &$taken = []): array
    {
        $batches = $this->queue($item)
            ->when($lock, fn (Builder $q) => $q->lockForUpdate())
            ->get();

        return $this->allocateFrom($batches, $quantity, $taken);
    }

    /**
     * Plan how to draw $quantity from batches already in draw order (from
     * queue() or queueFor()). The batches are not modified.
     *
     * $taken (batch id => units) holds what earlier lines of the same release
     * already planned to draw; only the rest of each batch is available, and
     * this plan's draws are added to it. Share one map across all lines of a
     * release so two lines for the same item never draw the same units.
     *
     * @param  Collection<int, InventoryBatches>  $batches
     * @param  array<int, int>  $taken
     * @return array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}
     */
    public function allocateFrom(Collection $batches, int $quantity, array &$taken = []): array
    {
        $allocations = collect();
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = $batch->remaining_qty - ($taken[$batch->id] ?? 0);

            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);
            $allocations->push(['batch' => $batch, 'quantity' => $take]);
            $taken[$batch->id] = ($taken[$batch->id] ?? 0) + $take;
            $remaining -= $take;
        }

        return ['allocations' => $allocations, 'shortfall' => max(0, $remaining)];
    }

    /**
     * Usable batches only (never expired), ordered FEFO or FIFO. The one place
     * the draw order is defined; queue() and queueFor() both go through it.
     *
     * @param  Builder<InventoryBatches>  $query
     * @return Builder<InventoryBatches>
     */
    protected function drawOrder(Builder $query, bool $fefo): Builder
    {
        return $query
            ->usable()
            ->when($fefo, fn (Builder $q) => $q
                ->orderByRaw('expiration_date is null') // undated batches last
                ->orderBy('expiration_date'))
            ->orderBy('received_at')
            ->orderBy('id');
    }
}
