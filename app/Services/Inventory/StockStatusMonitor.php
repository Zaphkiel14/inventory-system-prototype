<?php

namespace App\Services\Inventory;

use App\Enums\StockStatus;
use App\Models\InventoryItems;
use App\Models\User;
use App\Notifications\StockStatusAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Tracks each item's last known stock status and emails the opted-in users
 * when an item gets worse (Healthy → Reorder → Critical → Out). Improvements
 * update the stored status silently.
 *
 * Items are queued as stock moves and checked once the outermost database
 * transaction commits, so one action sends at most one email.
 */
class StockStatusMonitor
{
    /** @var array<int, true> */
    private array $pending = [];

    private bool $scheduled = false;

    private bool $muted = false;

    /**
     * Check the item once the current transaction commits (or right away when
     * no transaction is open).
     */
    public function queue(int $itemId): void
    {
        $this->pending[$itemId] = true;

        if ($this->scheduled && DB::transactionLevel() > 0) {
            return;
        }

        // Scheduled but no transaction is open: the transaction that held the
        // flush was rolled back without telling us. Schedule a fresh one.

        $this->scheduled = true;

        // Registered on the same transaction as the commit callback. On a
        // rollback the flush never runs, so allow the next queue() to
        // schedule one. Ids stay pending: check() reads the real stock, so
        // re-checking them later is harmless.
        DB::afterRollBack(function (): void {
            $this->scheduled = false;
        });

        DB::afterCommit(fn () => $this->flush());
    }

    /**
     * Check every pending item and send one alert for those that got worse.
     */
    public function flush(): void
    {
        $ids = array_keys($this->pending);

        $this->pending = [];
        $this->scheduled = false;

        if ($ids === []) {
            return;
        }

        $this->send($this->check($ids));
    }

    /**
     * Check every active item and alert on those that got worse. Catches
     * batches that expired without any stock movement.
     *
     * @return Collection<int, InventoryItems>
     */
    public function checkAll(): Collection
    {
        $alerts = $this->check(
            InventoryItems::query()->where('is_active', true)->pluck('id'),
        );

        $this->send($alerts);

        return $alerts;
    }

    /**
     * Recompute and store the status of the given active items.
     *
     * An item with no stored status gets its current one as a silent
     * baseline.
     *
     * @param  iterable<int|string>  $ids
     * @return Collection<int, InventoryItems> items whose new status needs a
     *                                         reorder and is worse than before, each with `previous_status`
     *                                         and `stock_status` set to StockStatus values
     */
    public function check(iterable $ids): Collection
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return InventoryItems::query()
            ->whereKey($ids->all())
            ->where('is_active', true)
            ->withAvailableQty()
            ->orderBy('name')
            ->get()
            ->filter(function (InventoryItems $item): bool {
                $previous = $item->stock_status;
                $current = $this->statusOf($item);

                if ($previous === $current) {
                    return false;
                }

                $this->store($item, $current);

                if ($previous === null) {
                    return false;
                }

                $item->setAttribute('previous_status', $previous);
                $item->setAttribute('stock_status', $current);

                return $current->needsReorder() && $current->isWorseThan($previous);
            })
            ->values();
    }

    /**
     * Run the callback without sending alerts. Statuses are still stored.
     */
    public function muted(callable $callback): mixed
    {
        $previous = $this->muted;
        $this->muted = true;

        try {
            return $callback();
        } finally {
            $this->muted = $previous;
        }
    }

    /**
     * Silently store the current status of every item, active or not.
     */
    public function baseline(): void
    {
        InventoryItems::query()
            ->withAvailableQty()
            ->lazyById()
            ->each(function (InventoryItems $item): void {
                $current = $this->statusOf($item);

                if ($item->stock_status !== $current) {
                    $this->store($item, $current);
                }
            });
    }

    private function statusOf(InventoryItems $item): StockStatus
    {
        return StockStatus::for(
            (int) $item->getAttribute('available_qty'),
            (int) $item->minimum_stock,
            (int) $item->reorder_level,
        );
    }

    /**
     * A query update, so the model's update hooks don't queue the item again.
     */
    private function store(InventoryItems $item, StockStatus $status): void
    {
        InventoryItems::query()->whereKey($item->getKey())->update(['stock_status' => $status->value]);
    }

    /**
     * @param  Collection<int, InventoryItems>  $alerts
     */
    private function send(Collection $alerts): void
    {
        if ($this->muted || $alerts->isEmpty()) {
            return;
        }

        $recipients = User::query()->where('notify_stock_alerts', true)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new StockStatusAlert($alerts));
    }
}
