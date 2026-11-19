<?php

namespace App\Services\Inventory;

use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use Illuminate\Database\Eloquent\Builder;
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
        return InventoryBatches::query()
            ->where('inventory_item_id', $item->id)
            ->usable()
            ->when($item->requires_expiry, fn (Builder $q) => $q
                ->orderByRaw('expiration_date is null') // undated batches last
                ->orderBy('expiration_date'))
            ->orderBy('received_at')
            ->orderBy('id');
    }

    /**
     * Plan how to draw $quantity from the item's batches. When $lock is true
     * the batches are locked for update (use inside a transaction).
     *
     * @return array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}
     */
    public function allocate(InventoryItems $item, int $quantity, bool $lock = false): array
    {
        $batches = $this->queue($item)
            ->when($lock, fn (Builder $q) => $q->lockForUpdate())
            ->get();

        $allocations = collect();
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $batch->remaining_qty);
            $allocations->push(['batch' => $batch, 'quantity' => $take]);
            $remaining -= $take;
        }

        return ['allocations' => $allocations, 'shortfall' => max(0, $remaining)];
    }
}
