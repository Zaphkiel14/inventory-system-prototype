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
use App\Services\Inventory\StockMetrics;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Period reports over the ledger and current stock.
 *
 * Deferred: the page paints a skeleton first and runs the report queries in a
 * request fired straight after paint.
 *
 * @property-read array{0: CarbonInterface, 1: CarbonInterface} $period
 * @property-read array{0: CarbonInterface, 1: CarbonInterface} $previousPeriod
 * @property-read string $bucket
 * @property-read list<array{day: string, type: ?InventoryTransactionType, net: int, written_down: int, gross: int}> $ledgerDays
 * @property-read array{out: int, critical: int, reorder: int, healthy: int} $stockHealth
 * @property-read Collection<int, InventoryItems> $reorderItems
 */
#[Defer]
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
     * Ledger units per day and type across the previous and the selected period,
     * aggregated in SQL. This one scan feeds the summary totals of both periods
     * and the movement chart.
     *
     * @return list<array{day: string, type: ?InventoryTransactionType, net: int, written_down: int, gross: int}>
     */
    #[Computed]
    public function ledgerDays(): array
    {
        [, $end] = $this->period;
        [$previousStart] = $this->previousPeriod;

        $rows = InventoryTransactions::query()
            ->whereBetween('created_at', [$previousStart, $end])
            ->selectRaw('date(created_at) as day, type')
            ->selectRaw('sum(quantity) as net')
            ->selectRaw('sum(case when quantity < 0 then -quantity else 0 end) as written_down')
            ->selectRaw('sum(abs(quantity)) as gross')
            ->groupByRaw('date(created_at), type')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'day' => (string) $row->day,
                'type' => InventoryTransactionType::tryFrom((int) $row->type),
                'net' => (int) $row->net,
                'written_down' => (int) $row->written_down,
                'gross' => (int) $row->gross,
            ])
            ->all();

        return array_values($rows);
    }

    /**
     * Ledger totals (received / issued / adjusted units) for a whole-day period,
     * matching StockMetrics::movementTotals() but read from ledgerDays().
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @return array{received: int, issued: int, adjusted: int}
     */
    protected function movementTotals(array $period): array
    {
        $from = $period[0]->toDateString();
        $to = $period[1]->toDateString();

        $net = fn (InventoryTransactionType $type): int => (int) collect($this->ledgerDays)
            ->filter(fn (array $row) => $row['type'] === $type && $row['day'] >= $from && $row['day'] <= $to)
            ->sum('net');

        return [
            'received' => $net(InventoryTransactionType::Receipt),
            'issued' => abs($net(InventoryTransactionType::Issuance)),
            'adjusted' => $net(InventoryTransactionType::Adjustment),
        ];
    }

    /**
     * @return array{stock_value: float, stock_units: int, expired_value: float, received: int, received_delta: ?float, issued: int, issued_delta: ?float, adjusted: int, adjusted_previous: int, needs_reorder: int, out_of_stock: int, expiring_soon: int}
     */
    #[Computed]
    public function summary(): array
    {
        $current = $this->movementTotals($this->period);
        $previous = $this->movementTotals($this->previousPeriod);

        // One round trip for the batch figures, each still defined by its own scope.
        $stock = DB::query()
            ->selectSub(InventoryBatches::query()->usable()->selectRaw('coalesce(sum(remaining_qty), 0)'), 'units')
            ->selectSub(InventoryBatches::query()->usable()->selectRaw('coalesce(sum(remaining_qty * unit_cost), 0)'), 'value')
            ->selectSub(InventoryBatches::query()->expired()->selectRaw('coalesce(sum(remaining_qty * unit_cost), 0)'), 'expired_value')
            ->selectSub(InventoryBatches::query()->expiringWithin(30)->selectRaw('count(*)'), 'expiring_soon')
            ->first();

        $health = $this->stockHealth;

        return [
            'stock_value' => (float) $stock->value,
            'stock_units' => (int) $stock->units,
            'expired_value' => (float) $stock->expired_value,
            'received' => $current['received'],
            'received_delta' => $this->delta($current['received'], $previous['received']),
            'issued' => $current['issued'],
            'issued_delta' => $this->delta($current['issued'], $previous['issued']),
            'adjusted' => $current['adjusted'],
            'adjusted_previous' => $previous['adjusted'],
            'needs_reorder' => $health['out'] + $health['critical'] + $health['reorder'],
            'out_of_stock' => $health['out'],
            'expiring_soon' => (int) $stock->expiring_soon,
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
     *
     * @return list<array{date: string, received: int, issued: int, written_down: int}>
     */
    #[Computed]
    public function movement(): array
    {
        [$start, $end] = $this->period;
        $from = $start->toDateString();

        $rows = [];
        for ($cursor = Carbon::parse($this->bucketKey($start)); $cursor->lte($end); $cursor = match ($this->bucket) {
            'day' => $cursor->addDay(),
            'week' => $cursor->addWeek(),
            default => $cursor->addMonth(),
        }) {
            $rows[$cursor->toDateString()] = ['date' => $cursor->toDateString(), 'received' => 0, 'issued' => 0, 'written_down' => 0];
        }

        foreach ($this->ledgerDays as $day) {
            // ledgerDays() also covers the previous period; the chart shows only this one.
            if ($day['day'] < $from) {
                continue;
            }

            $key = $this->bucketKey(Carbon::parse($day['day']));

            if (! isset($rows[$key])) {
                continue;
            }

            match ($day['type']) {
                InventoryTransactionType::Receipt => $rows[$key]['received'] += $day['net'],
                InventoryTransactionType::Issuance => $rows[$key]['issued'] += $day['gross'],
                InventoryTransactionType::Adjustment => $rows[$key]['written_down'] += $day['written_down'],
                default => null,
            };
        }

        return array_values($rows);
    }

    /**
     * Active items grouped by how their usable stock compares to their thresholds.
     */
    /**
     * @return array{out: int, critical: int, reorder: int, healthy: int}
     */
    #[Computed]
    public function stockHealth(): array
    {
        return app(StockMetrics::class)->stockHealth();
    }

    /**
     * Value and units received per supplier in the period (top 6 by value).
     *
     * @return Collection<int, array{supplier: string, value: float, units: int, donated_value: float, purchased_value: float, deliveries: int}>
     */
    #[Computed]
    public function receivingBySupplier(): Collection
    {
        return InventoryBatches::query()
            ->join('stock_receipts', 'stock_receipts.id', '=', 'inventory_batches.stock_receipt_id')
            ->join('suppliers', 'suppliers.id', '=', 'stock_receipts.supplier_id')
            ->whereBetween('stock_receipts.received_date', $this->period)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->selectRaw('suppliers.name as supplier, sum(inventory_batches.received_qty) as units, sum(inventory_batches.received_qty * inventory_batches.unit_cost) as value, sum(case when stock_receipts.is_donation then inventory_batches.received_qty * inventory_batches.unit_cost else 0 end) as donated_value, count(distinct stock_receipts.id) as deliveries')
            ->orderByDesc('value')
            ->limit(6)
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'supplier' => (string) $row->supplier,
                'value' => round((float) $row->value, 2),
                'units' => (int) $row->units,
                'donated_value' => round((float) $row->donated_value, 2),
                'purchased_value' => round((float) $row->value - (float) $row->donated_value, 2),
                'deliveries' => (int) $row->deliveries,
            ]);
    }

    /**
     * Value of usable stock currently held at each storage location.
     *
     * @return Collection<int, array{location: string, value: float, units: int}>
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
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'location' => (string) $row->location,
                'value' => round((float) $row->value, 2),
                'units' => (int) $row->units,
            ]);
    }

    /**
     * Items with the most units issued in the period (top 8).
     *
     * @return Collection<int, array{item: string, units: int}>
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
            ->toBase()
            ->get()
            ->map(fn (object $row): array => ['item' => (string) $row->item, 'units' => (int) $row->units]);
    }

    /**
     * Adjustments recorded in the period, per type: count and net units.
     *
     * @return Collection<int, array{type: AdjustmentType, label: string, count: int, units: int}>
     */
    #[Computed]
    public function adjustmentsByType(): Collection
    {
        $rows = InventoryAdjustments::query()
            ->whereBetween('created_at', $this->period)
            ->groupBy('type')
            ->selectRaw('type, count(*) as count, sum(changed_qty) as units')
            ->toBase()
            ->get()
            ->keyBy('type');

        return collect(AdjustmentType::cases())
            ->filter(fn (AdjustmentType $type): bool => (int) ($rows->get($type->value)->count ?? 0) > 0)
            ->map(fn (AdjustmentType $type): array => [
                'type' => $type,
                'label' => $type->label(),
                'count' => (int) $rows->get($type->value)->count,
                'units' => (int) ($rows->get($type->value)->units ?? 0),
            ])
            ->values();
    }

    /**
     * Requisitions submitted in the period, per status.
     *
     * @return Collection<int, array{status: RequisitionStatus, label: string, count: int}>
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
            ->filter(fn (RequisitionStatus $status): bool => (int) ($counts[$status->value] ?? 0) > 0)
            ->map(fn (RequisitionStatus $status): array => [
                'status' => $status,
                'label' => $status->label(),
                'count' => (int) $counts[$status->value],
            ])
            ->values();
    }

    /**
     * Active items at or below their reorder level, most urgent first.
     *
     * @return Collection<int, InventoryItems>
     */
    #[Computed]
    public function reorderItems(): Collection
    {
        return app(StockMetrics::class)->reorderItems();
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.reports');
    }

    public function render(): View
    {
        return view('livewire.monitoring.reports');
    }
}
