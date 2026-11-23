<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Enums\StockStatus;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Stock figures shared by the dashboard and the Reports page.
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
    }

    /**
     * Received / issued / written-down units per day, with empty days kept so
     * the time axis is continuous.
     *
     * @return list<array{date: string, received: int, issued: int, written_down: int}>
     */
    public function dailyMovement(CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = [];
        for ($cursor = Carbon::parse($start)->startOfDay(); $cursor->lte($end); $cursor->addDay()) {
            $rows[$cursor->toDateString()] = ['date' => $cursor->toDateString(), 'received' => 0, 'issued' => 0, 'written_down' => 0];
        }

        InventoryTransactions::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['type', 'quantity', 'created_at'])
            ->each(function (InventoryTransactions $t) use (&$rows) {
                $key = $t->created_at->toDateString();

                if (! isset($rows[$key])) {
                    return;
                }

                match ($t->type) {
                    InventoryTransactionType::Receipt => $rows[$key]['received'] += $t->quantity,
                    InventoryTransactionType::Issuance => $rows[$key]['issued'] += abs($t->quantity),
                    InventoryTransactionType::Adjustment => $rows[$key]['written_down'] += max(0, -$t->quantity),
                };
            });

        return array_values($rows);
    }

    /**
     * Active items grouped by how their usable stock compares to their thresholds.
     *
     * @return array{out: int, critical: int, reorder: int, healthy: int}
     */
    public function stockHealth(): array
    {
        $counts = ['out' => 0, 'critical' => 0, 'reorder' => 0, 'healthy' => 0];

        InventoryItems::query()
            ->where('is_active', true)
            ->withAvailableQty()
            ->get(['id', 'minimum_stock', 'reorder_level'])
            ->each(function (InventoryItems $item) use (&$counts) {
                $counts[$this->healthOf($item)]++;
            });

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
        return InventoryItems::query()
            ->where('is_active', true)
            ->withAvailableQty()
            ->withMax('inventoryBatches as last_received_at', 'received_at')
            ->get()
            ->filter(fn (InventoryItems $item) => (int) $item->available_qty <= $item->reorder_level)
            ->map(fn (InventoryItems $item) => tap($item, function ($item) {
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
}
