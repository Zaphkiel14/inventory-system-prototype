<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\StockStatus;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Support\InventoryCache;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use stdClass;

/**
 * Stock figures shared by the dashboard and the Reports page.
 *
 * Results are cached under the ledger version (see InventoryCache), so they
 * are rebuilt after any stock movement, batch or item change.
 */
class StockMetrics
{
    /**
     * Ledger totals (received / issued / adjusted units) for a period.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @return array{received: int, issued: int, adjusted: int}
     */
    public function movementTotals(array $period): array
    {
        return InventoryCache::ledger('metrics:movement-totals', $this->periodKey($period), function () use ($period): array {
            $totals = InventoryTransactions::query()
                ->whereBetween('created_at', $period)
                ->selectRaw('type, sum(quantity) as total')
                ->groupBy('type')
                ->pluck('total', 'type');

            return [
                'received' => (int) ($totals[InventoryTransactionType::Receipt->value] ?? 0),
                'issued' => abs((int) ($totals[InventoryTransactionType::Issuance->value] ?? 0)),
                'adjusted' => (int) ($totals[InventoryTransactionType::Adjustment->value] ?? 0),
            ];
        });
    }

    /**
     * Received / issued / written-down units per day, with empty days kept so
     * the time axis is continuous.
     *
     * Grouped by day in SQL: created_at is stored in the app timezone, so
     * date(created_at) is the same day as created_at->toDateString().
     *
     * @return list<array{date: string, received: int, issued: int, written_down: int}>
     */
    public function dailyMovement(CarbonInterface $start, CarbonInterface $end): array
    {
        return InventoryCache::ledger('metrics:daily-movement', $this->periodKey([$start, $end]), function () use ($start, $end): array {
            $rows = [];
            for ($cursor = Carbon::parse($start)->startOfDay(); $cursor->lte($end); $cursor->addDay()) {
                $rows[$cursor->toDateString()] = ['date' => $cursor->toDateString(), 'received' => 0, 'issued' => 0, 'written_down' => 0];
            }

            InventoryTransactions::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('date(created_at) as day, type')
                ->selectRaw('sum(quantity) as total')
                ->selectRaw('sum(abs(quantity)) as moved')
                ->selectRaw('sum(case when quantity < 0 then -quantity else 0 end) as written_down')
                ->groupByRaw('date(created_at), type')
                ->toBase()
                ->get()
                ->each(function (object $day) use (&$rows) {
                    if (! isset($rows[$day->day])) {
                        return;
                    }

                    match (InventoryTransactionType::from((int) $day->type)) {
                        InventoryTransactionType::Receipt => $rows[$day->day]['received'] += (int) $day->total,
                        InventoryTransactionType::Issuance => $rows[$day->day]['issued'] += (int) $day->moved,
                        InventoryTransactionType::Adjustment => $rows[$day->day]['written_down'] += (int) $day->written_down,
                    };
                });

            return array_values($rows);
        });
    }

    /**
     * Active items grouped by how their usable stock compares to their thresholds.
     *
     * @return array{out: int, critical: int, reorder: int, healthy: int}
     */
    public function stockHealth(): array
    {
        $counts = ['out' => 0, 'critical' => 0, 'reorder' => 0, 'healthy' => 0];

        foreach ($this->activeItems() as $item) {
            $counts[StockStatus::for($item['available_qty'], $item['minimum_stock'], $item['reorder_level'])->value]++;
        }

        return $counts;
    }

    /**
     * Active items at or below their reorder level, most urgent first. Each item
     * gets `health` (a StockStatus value) and `shortfall`.
     *
     * @return Collection<int, InventoryItems>
     */
    public function reorderItems(): Collection
    {
        return collect($this->activeItems())
            ->filter(fn (array $item) => $item['available_qty'] <= $item['reorder_level'])
            ->map(fn (array $item) => tap((new InventoryItems)->newFromBuilder($item), function (InventoryItems $item) {
                $item->health = $this->healthOf($item);
                $item->shortfall = max(0, $item->reorder_level - (int) $item->available_qty);
            }))
            ->sortBy([
                fn ($a, $b) => StockStatus::from($a->health)->urgency() <=> StockStatus::from($b->health)->urgency(),
                fn ($a, $b) => $b->shortfall <=> $a->shortfall,
            ])
            ->values();
    }

    public function healthOf(InventoryItems $item): string
    {
        return StockStatus::for((int) $item->available_qty, $item->minimum_stock, $item->reorder_level)->value;
    }

    /**
     * Every active item with its usable stock: the one load behind
     * stockHealth() and reorderItems(). Plain arrays (the cache refuses objects).
     *
     * @return list<array{id: int, name: string, item_code: string, unit: string, minimum_stock: int, reorder_level: int, available_qty: int, last_received_at: ?string}>
     */
    protected function activeItems(): array
    {
        return InventoryCache::ledger('metrics:active-items', [], fn (): array => array_values(InventoryItems::query()
            ->where('is_active', true)
            ->select(['id', 'name', 'item_code', 'unit', 'minimum_stock', 'reorder_level'])
            ->withAvailableQty()
            ->withMax('inventoryBatches as last_received_at', 'received_at')
            ->toBase()
            ->get()
            ->map(fn (stdClass $item) => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'item_code' => (string) $item->item_code,
                'unit' => (string) $item->unit,
                'minimum_stock' => (int) $item->minimum_stock,
                'reorder_level' => (int) $item->reorder_level,
                'available_qty' => (int) $item->available_qty,
                'last_received_at' => $item->last_received_at === null ? null : (string) $item->last_received_at,
            ])
            ->all()));
    }

    /**
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @return array{0: string, 1: string}
     */
    protected function periodKey(array $period): array
    {
        return [$period[0]->toIso8601String(), $period[1]->toIso8601String()];
    }
}
