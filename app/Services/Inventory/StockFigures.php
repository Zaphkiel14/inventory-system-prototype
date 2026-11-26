<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\StockStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Support\InventoryCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Item-level stock figures for Stock Monitoring: the cached per-item figures,
 * the decorated row built from them, and an item's drill-down. Shared by the
 * list (StockMonitoring) and the lazily loaded row detail (StockDetail), so
 * both read the same numbers.
 */
class StockFigures
{
    /** Days of issuance history used for the usage rate and days of cover. */
    public const USAGE_DAYS = 90;

    /** Days of balance history shown in the item drill-down. */
    public const TREND_DAYS = 90;

    /**
     * Stock figures for every active item, keyed by item id. This is the
     * heavy part (one aggregate subquery per figure), so it is cached under
     * the ledger version: any stock movement, batch or item change rebuilds it.
     * Plain arrays only (the cache refuses objects).
     *
     * @return array<int, array{minimum_stock: int, reorder_level: int, available_qty: int, committed_qty: int, usable_batches: int, next_expiry: ?string, expired_qty: int, issued_recent: int, last_received_at: ?string, value: float}>
     */
    public function all(): array
    {
        return InventoryCache::ledger('stock-monitoring:figures', [today()->toDateString(), self::USAGE_DAYS], function (): array {
            $values = InventoryBatches::query()->usable()
                ->groupBy('inventory_item_id')
                ->selectRaw('inventory_item_id, sum(remaining_qty * unit_cost) as value')
                ->pluck('value', 'inventory_item_id');

            return InventoryItems::query()
                ->where('is_active', true)
                ->select(['inventory_items.id', 'inventory_items.minimum_stock', 'inventory_items.reorder_level'])
                ->withAvailableQty()
                ->withCommittedQty()
                ->withCount(['inventoryBatches as usable_batches' => fn (Builder $q) => $q->usable()])
                ->withMin(['inventoryBatches as next_expiry' => fn (Builder $q) => $q->usable()], 'expiration_date')
                ->withSum(['inventoryBatches as expired_qty' => fn (Builder $q) => $q->expired()], 'remaining_qty')
                ->withSum(['inventoryTransactions as issued_recent' => fn (Builder $q) => $q
                    ->where('type', InventoryTransactionType::Issuance)
                    ->where('created_at', '>=', now()->subDays(self::USAGE_DAYS))], 'quantity')
                ->withMax('inventoryBatches as last_received_at', 'received_at')
                ->toBase()
                ->get()
                ->mapWithKeys(fn (object $row) => [(int) $row->id => [
                    'minimum_stock' => (int) $row->minimum_stock,
                    'reorder_level' => (int) $row->reorder_level,
                    'available_qty' => (int) $row->available_qty,
                    'committed_qty' => (int) $row->committed_qty,
                    'usable_batches' => (int) $row->usable_batches,
                    'next_expiry' => $row->next_expiry,
                    'expired_qty' => (int) $row->expired_qty,
                    'issued_recent' => (int) $row->issued_recent,
                    'last_received_at' => $row->last_received_at,
                    'value' => (float) ($values[$row->id] ?? 0),
                ]])
                ->all();
        });
    }

    /**
     * Put an item's figures and derived stock fields on it (the row the list
     * and the drill-down display).
     *
     * @param  array{minimum_stock: int, reorder_level: int, available_qty: int, committed_qty: int, usable_batches: int, next_expiry: ?string, expired_qty: int, issued_recent: int, last_received_at: ?string, value: float}|null  $figures
     */
    public function decorate(InventoryItems $item, ?array $figures): InventoryItems
    {
        $item->available_qty = $figures['available_qty'] ?? 0;
        $item->committed_qty = $figures['committed_qty'] ?? 0;
        $item->usable_batches = $figures['usable_batches'] ?? 0;
        $item->next_expiry = $figures['next_expiry'] ?? null;
        $item->expired_qty = $figures['expired_qty'] ?? 0;
        $item->issued_recent = $figures['issued_recent'] ?? 0;
        $item->last_received_at = $figures['last_received_at'] ?? null;

        $item->available = (int) $item->available_qty;
        $item->committed = min((int) $item->committed_qty, $item->available);
        $item->free = $item->available - $item->committed;
        $item->expired = (int) $item->expired_qty;
        $item->value = (float) ($figures['value'] ?? 0);
        $item->stock_status = StockStatus::for($item->available, $item->minimum_stock, $item->reorder_level);
        $item->daily_usage = (float) abs((int) $item->issued_recent) / self::USAGE_DAYS;
        $item->cover_days = $item->daily_usage > 0 ? (int) floor($item->free / $item->daily_usage) : null;

        return $item;
    }

    /**
     * Batches, balance history and recent movement for one decorated item.
     *
     * @return array{item: InventoryItems, method: string, batches: array<int, array{batch: InventoryBatches, order: ?int, reserved: int, expired: bool}>, trend: list<array{date: string, balance: int}>, movement: array{received: int, issued: int, adjusted: int}}
     */
    public function detail(InventoryItems $item): array
    {
        $allocator = app(BatchAllocator::class);

        // Batches in the exact order issuance will draw from them, with the
        // stock already promised to approved requests reserved from the front.
        $queue = $allocator->queue($item)->with('storageLocation')->get();
        $reserved = $allocator->allocateFrom($queue, $item->committed)['allocations']
            ->mapWithKeys(fn (array $a) => [$a['batch']->id => $a['quantity']]);

        $usable = $queue->values()->map(fn (InventoryBatches $batch, int $i) => [
            'batch' => $batch,
            'order' => $i + 1,
            'reserved' => (int) ($reserved[$batch->id] ?? 0),
            'expired' => false,
        ]);

        $expired = InventoryBatches::query()
            ->with('storageLocation')
            ->where('inventory_item_id', $item->id)
            ->expired()
            ->orderBy('expiration_date')
            ->get()
            ->map(fn (InventoryBatches $batch) => ['batch' => $batch, 'order' => null, 'reserved' => 0, 'expired' => true]);

        return [
            'item' => $item,
            'method' => BatchAllocator::methodFor($item),
            'batches' => $usable->concat($expired)->all(),
            ...$this->history($item),
        ];
    }

    /**
     * End-of-day on-hand balance for the last TREND_DAYS days, rebuilt backwards
     * from the current total so it doesn't depend on stored balance_after values,
     * plus received / issued / adjusted totals for the same window.
     *
     * @return array{trend: list<array{date: string, balance: int}>, movement: array{received: int, issued: int, adjusted: int}}
     */
    protected function history(InventoryItems $item): array
    {
        $start = today()->subDays(self::TREND_DAYS - 1);
        $onHand = (int) InventoryBatches::where('inventory_item_id', $item->id)->sum('remaining_qty');

        $transactions = InventoryTransactions::query()
            ->where('inventory_item_id', $item->id)
            ->where('created_at', '>=', $start)
            ->get(['type', 'quantity', 'created_at']);

        $byDay = $transactions->groupBy(fn ($t) => $t->created_at->toDateString())
            ->map(fn (Collection $day) => (int) $day->sum('quantity'));

        $trend = [];
        $balance = $onHand;
        for ($day = today(); $day->gte($start); $day = $day->subDay()) {
            $date = $day->toDateString();
            $trend[] = ['date' => $date, 'balance' => $balance];
            $balance -= $byDay[$date] ?? 0;
        }

        $sum = fn (InventoryTransactionType $type) => (int) $transactions->where('type', $type)->sum('quantity');

        return [
            'trend' => array_reverse($trend),
            'movement' => [
                'received' => $sum(InventoryTransactionType::Receipt),
                'issued' => abs($sum(InventoryTransactionType::Issuance)),
                'adjusted' => $sum(InventoryTransactionType::Adjustment),
            ],
        ];
    }
}
