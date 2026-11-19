<?php

namespace App\Livewire\Monitoring;

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Reports extends Component
{
    /**
     * Range options => label.
     */
    public const RANGES = [
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        '180' => 'Last 6 months',
        '365' => 'Last 12 months',
        'ytd' => 'Year to date',
        'custom' => 'Custom range',
    ];

    #[Url]
    public string $range = '90';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public function mount(): void
    {
        $this->normalizeRange();
    }

    public function updatedRange(): void
    {
        $this->normalizeRange();

        if ($this->range === 'custom' && blank($this->from) && blank($this->to)) {
            $this->from = today()->subDays(29)->toDateString();
            $this->to = today()->toDateString();
        }

        if ($this->range !== 'custom') {
            $this->reset('from', 'to');
        }
    }

    protected function normalizeRange(): void
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = '90';
        }
    }

    /* ------------------------------------------------------------------ */
    /* Period helpers */
    /* ------------------------------------------------------------------ */

    /**
     * Start and end of the selected period (whole days, inclusive).
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    #[Computed]
    public function period(): array
    {
        $end = today()->endOfDay();

        $start = match ($this->range) {
            'ytd' => today()->startOfYear(),
            'custom' => $this->parseDate($this->from) ?? today()->subDays(29),
            default => today()->subDays((int) $this->range - 1),
        };

        if ($this->range === 'custom') {
            $end = ($this->parseDate($this->to) ?? today())->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start->startOfDay(), $end];
    }

    /**
     * The equal-length period immediately before the selected one.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    #[Computed]
    public function previousPeriod(): array
    {
        [$start, $end] = $this->period;
        $days = (int) $start->diffInDays($end) + 1;

        return [$start->copy()->subDays($days), $start->copy()->subSecond()];
    }

    /**
     * Chart granularity: daily for a month, weekly up to ~4 months, else monthly.
     */
    #[Computed]
    public function bucket(): string
    {
        [$start, $end] = $this->period;
        $days = $start->diffInDays($end) + 1;

        return match (true) {
            $days <= 31 => 'day',
            $days <= 120 => 'week',
            default => 'month',
        };
    }

    protected function parseDate(?string $value): ?CarbonInterface
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function bucketKey(CarbonInterface $date): string
    {
        return match ($this->bucket) {
            'day' => $date->toDateString(),
            'week' => $date->copy()->startOfWeek()->toDateString(),
            default => $date->copy()->startOfMonth()->toDateString(),
        };
    }

    /* ------------------------------------------------------------------ */
    /* Summary */
    /* ------------------------------------------------------------------ */

    /**
     * Ledger totals (received / issued / adjusted units) for a period.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @return array{received: int, issued: int, adjusted: int}
     */
    protected function movementTotals(array $period): array
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

    #[Computed]
    public function summary(): array
    {
        $current = $this->movementTotals($this->period);
        $previous = $this->movementTotals($this->previousPeriod);

        $onHand = InventoryBatches::query()->usable()
            ->selectRaw('coalesce(sum(remaining_qty), 0) as units, coalesce(sum(remaining_qty * unit_cost), 0) as value')
            ->first();

        $expiredValue = (float) InventoryBatches::query()
            ->where('remaining_qty', '>', 0)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', today()->toDateString())
            ->sum(DB::raw('remaining_qty * unit_cost'));

        $health = $this->stockHealth;

        return [
            'stock_value' => (float) $onHand->value,
            'stock_units' => (int) $onHand->units,
            'expired_value' => $expiredValue,
            'received' => $current['received'],
            'received_delta' => $this->delta($current['received'], $previous['received']),
            'issued' => $current['issued'],
            'issued_delta' => $this->delta($current['issued'], $previous['issued']),
            'adjusted' => $current['adjusted'],
            'adjusted_previous' => $previous['adjusted'],
            'needs_reorder' => $health['out'] + $health['critical'] + $health['reorder'],
            'out_of_stock' => $health['out'],
            'expiring_soon' => InventoryBatches::query()
                ->where('remaining_qty', '>', 0)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '>=', today()->toDateString())
                ->whereDate('expiration_date', '<=', today()->addDays(30)->toDateString())
                ->count(),
        ];
    }

    /**
     * Percentage change from the previous period, or null when there's no baseline.
     */
    protected function delta(int|float $current, int|float $previous): ?float
    {
        if ($previous == 0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /* ------------------------------------------------------------------ */
    /* Charts */
    /* ------------------------------------------------------------------ */

    /**
     * Received / issued / written-down units per bucket, with empty buckets kept
     * so the time axis is continuous.
     */
    #[Computed]
    public function movement(): array
    {
        [$start, $end] = $this->period;

        $rows = [];
        for ($cursor = Carbon::parse($this->bucketKey($start)); $cursor->lte($end); $cursor = match ($this->bucket) {
            'day' => $cursor->addDay(),
            'week' => $cursor->addWeek(),
            default => $cursor->addMonth(),
        }) {
            $rows[$cursor->toDateString()] = ['date' => $cursor->toDateString(), 'received' => 0, 'issued' => 0, 'written_down' => 0];
        }

        InventoryTransactions::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['type', 'quantity', 'created_at'])
            ->each(function (InventoryTransactions $t) use (&$rows) {
                $key = $this->bucketKey($t->created_at);

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
     */
    #[Computed]
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

    protected function healthOf(InventoryItems $item): string
    {
        $available = (int) $item->available_qty;

        return match (true) {
            $available <= 0 => 'out',
            $available <= $item->minimum_stock => 'critical',
            $available <= $item->reorder_level => 'reorder',
            default => 'healthy',
        };
    }

    /**
     * Value and units received per supplier in the period (top 6 by value).
     */
    #[Computed]
    public function receivingBySupplier(): Collection
    {
        return InventoryBatches::query()
            ->join('stock_receipts', 'stock_receipts.id', '=', 'inventory_batches.stock_receipt_id')
            ->join('suppliers', 'suppliers.id', '=', 'stock_receipts.supplier_id')
            ->whereBetween('stock_receipts.received_date', $this->period)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->selectRaw('suppliers.name as supplier, sum(inventory_batches.received_qty) as units, sum(inventory_batches.received_qty * inventory_batches.unit_cost) as value, count(distinct stock_receipts.id) as deliveries')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'supplier' => $row->supplier,
                'value' => round((float) $row->value, 2),
                'units' => (int) $row->units,
                'deliveries' => (int) $row->deliveries,
            ]);
    }

    /**
     * Value of usable stock currently held at each storage location.
     */
    #[Computed]
    public function stockByLocation(): Collection
    {
        return InventoryBatches::query()->usable()
            ->join('storage_locations', 'storage_locations.id', '=', 'inventory_batches.storage_location_id')
            ->groupBy('storage_locations.id', 'storage_locations.name')
            ->selectRaw('storage_locations.name as location, sum(remaining_qty) as units, sum(remaining_qty * unit_cost) as value')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'location' => $row->location,
                'value' => round((float) $row->value, 2),
                'units' => (int) $row->units,
            ]);
    }

    /**
     * Items with the most units issued in the period (top 8).
     */
    #[Computed]
    public function topIssued(): Collection
    {
        return InventoryTransactions::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_transactions.inventory_item_id')
            ->where('inventory_transactions.type', InventoryTransactionType::Issuance)
            ->whereBetween('inventory_transactions.created_at', $this->period)
            ->groupBy('inventory_items.id', 'inventory_items.name')
            ->selectRaw('inventory_items.name as item, -sum(inventory_transactions.quantity) as units')
            ->orderByDesc('units')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['item' => $row->item, 'units' => (int) $row->units]);
    }

    /**
     * Adjustments recorded in the period, per type: count and net units.
     */
    #[Computed]
    public function adjustmentsByType(): Collection
    {
        $rows = InventoryAdjustments::query()
            ->whereBetween('created_at', $this->period)
            ->groupBy('type')
            ->selectRaw('type, count(*) as count, sum(changed_qty) as units')
            ->get()
            ->keyBy(fn ($row) => $row->type->value);

        return collect(AdjustmentType::cases())
            ->map(fn (AdjustmentType $type) => [
                'type' => $type,
                'label' => $type->label(),
                'count' => (int) ($rows[$type->value]->count ?? 0),
                'units' => (int) ($rows[$type->value]->units ?? 0),
            ])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();
    }

    /**
     * Requisitions submitted in the period, per status.
     */
    #[Computed]
    public function requisitionsByStatus(): Collection
    {
        $counts = Requisitions::query()
            ->whereBetween('submitted_at', $this->period)
            ->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status');

        return collect(RequisitionStatus::cases())
            ->map(fn (RequisitionStatus $status) => [
                'status' => $status,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();
    }

    /**
     * Active items at or below their reorder level, most urgent first.
     */
    #[Computed]
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
                fn ($a, $b) => array_search($a->health, ['out', 'critical', 'reorder']) <=> array_search($b->health, ['out', 'critical', 'reorder']),
                fn ($a, $b) => $b->shortfall <=> $a->shortfall,
            ])
            ->values();
    }

    public function render()
    {
        return view('livewire.monitoring.reports');
    }
}
