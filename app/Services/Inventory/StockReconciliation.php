<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockIssuanceBatches;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only audit of the stock ledger: does opening + movements = expected, and
 * does expected match what the batches (or the last ledger balance) say?
 * Never writes batches or transactions.
 *
 * @phpstan-type Filters array{category?: ?int, location?: ?int, search?: ?string, status?: ?string}
 */
class StockReconciliation
{
    /** @var array<string, Collection<int, array<string, mixed>>> */
    protected array $exceptionCache = [];

    /**
     * One row per item with opening, movements, expected vs actual and variance.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function summary(array $period, array $filters = []): Collection
    {
        [$start, $end] = $period;
        $startSql = $start->format('Y-m-d H:i:s');
        $endSql = $end->format('Y-m-d H:i:s');
        $receipt = (string) InventoryTransactionType::Receipt->value;
        $issuance = (string) InventoryTransactionType::Issuance->value;
        $adjustment = (string) InventoryTransactionType::Adjustment->value;
        $location = $filters['location'] ?? null;

        $items = $this->itemScope($filters)->orderBy('name')->get(['inventory_items.id', 'inventory_items.name', 'inventory_items.unit']);

        $ledger = $this->transactionQuery($location)
            ->selectRaw('inventory_item_id')
            ->selectRaw('coalesce(sum(case when created_at < ? then quantity else 0 end), 0) as opening', [$startSql])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as received', [$startSql, $endSql, $receipt])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as issued', [$startSql, $endSql, $issuance])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as adjusted', [$startSql, $endSql, $adjustment])
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        $recorded = $this->transactionQuery($location, 't')
            ->from('inventory_transactions as t')
            ->where('t.created_at', '<=', $endSql)
            ->whereNotExists(function ($q) use ($endSql, $location) {
                $q->selectRaw('1')
                    ->from('inventory_transactions as t2')
                    ->whereColumn('t2.inventory_item_id', 't.inventory_item_id')
                    ->where('t2.created_at', '<=', $endSql)
                    ->where(fn ($w) => $w
                        ->whereColumn('t2.created_at', '>', 't.created_at')
                        ->orWhere(fn ($x) => $x
                            ->whereColumn('t2.created_at', 't.created_at')
                            ->whereColumn('t2.id', '>', 't.id')));

                if ($location) {
                    $q->whereIn('t2.batch_id', $this->locationBatchIds($location));
                }
            })
            ->pluck('t.balance_after', 't.inventory_item_id');

        $asOfToday = $this->asOfToday($end);

        $batches = $asOfToday
            ? InventoryBatches::query()
                ->when($location, fn (Builder $q) => $q->where('storage_location_id', $location))
                ->selectRaw('inventory_item_id, coalesce(sum(remaining_qty), 0) as total')
                ->groupBy('inventory_item_id')
                ->pluck('total', 'inventory_item_id')
            : collect();

        $exceptionsByItem = $this->exceptions($period, $filters)->groupBy('item_id');

        $rows = $items->map(/** @return array<string, mixed> */ function (InventoryItems $item) use ($ledger, $recorded, $batches, $asOfToday, $location, $exceptionsByItem): array {
            $l = $ledger->get($item->id);
            $opening = (int) ($l->opening ?? 0);
            $received = (int) ($l->received ?? 0);
            $issued = abs((int) ($l->issued ?? 0));
            $adjusted = (int) ($l->adjusted ?? 0);
            $expected = $opening + $received - $issued + $adjusted;
            $rec = (int) ($recorded[$item->id] ?? 0);
            $unverified = ! $asOfToday && $location !== null;
            $actual = $unverified ? null : ($asOfToday ? (int) ($batches[$item->id] ?? 0) : $rec);
            $variance = $actual === null ? null : $actual - $expected;
            $itemExceptions = $exceptionsByItem->get($item->id, collect());

            return [
                'item_id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'opening' => $opening,
                'received' => $received,
                'issued' => $issued,
                'adjusted' => $adjusted,
                'expected' => $expected,
                'recorded' => $rec,
                'actual' => $actual,
                'actual_source' => $unverified ? 'unavailable' : ($asOfToday ? 'batches' : 'ledger'),
                'variance' => $variance,
                'status' => $variance === null ? 'unverified' : ($variance === 0 ? 'balanced' : ($variance < 0 ? 'short' : 'overflow')),
                'exception_count' => $itemExceptions->count(),
                'exception_kinds' => $itemExceptions->countBy('kind')->sortDesc()->all(),
            ];
        });

        $status = $filters['status'] ?? 'all';

        return $rows
            ->filter(fn (array $row) => match ($status) {
                'short', 'overflow', 'balanced' => $row['status'] === $status,
                'exceptions' => $row['exception_count'] > 0,
                default => true,
            })
            ->values();
    }

    /**
     * Integrity problems in the ledger and batches for the period.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function exceptions(array $period, array $filters = []): Collection
    {
        $key = md5(serialize([$period[0]->toIso8601String(), $period[1]->toIso8601String(), $filters['category'] ?? null, $filters['location'] ?? null, $filters['search'] ?? null]));

        return $this->exceptionCache[$key] ??= $this->buildExceptions($period, $filters);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{items: int, balanced: int, short: int, overflow: int, unverified: int, variance_units: int}
     */
    public function totals(Collection $rows): array
    {
        return [
            'items' => $rows->count(),
            'balanced' => $rows->where('status', 'balanced')->count(),
            'short' => $rows->where('status', 'short')->count(),
            'overflow' => $rows->where('status', 'overflow')->count(),
            'unverified' => $rows->where('status', 'unverified')->count(),
            'variance_units' => (int) $rows->sum(fn (array $r) => $r['variance'] ?? 0),
        ];
    }

    /* ------------------------------------------------------------------ */

    /**
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildExceptions(array $period, array $filters): Collection
    {
        [$start, $end] = $period;
        $startSql = $start->format('Y-m-d H:i:s');
        $endSql = $end->format('Y-m-d H:i:s');
        $location = $filters['location'] ?? null;
        $out = collect();

        $itemIds = fn () => $this->itemScope($filters)->select('inventory_items.id');
        $names = $this->itemScope($filters)->pluck('inventory_items.name', 'inventory_items.id');
        $locationBatches = $location ? array_flip($this->locationBatchIds($location)->pluck('id')->all()) : null;

        // 1 and 5 (ledger part): walk each item's full ledger in order.
        $previous = [];
        InventoryTransactions::query()
            ->whereIn('inventory_item_id', $itemIds())
            ->where('created_at', '<=', $endSql)
            ->orderBy('inventory_item_id')->orderBy('created_at')->orderBy('id')
            ->with('inventoryBatch:id,batch_number')
            ->lazy(1000)
            ->each(function (InventoryTransactions $t) use (&$previous, &$out, $start, $names, $locationBatches) {
                $itemId = (int) $t->inventory_item_id;
                $expected = ($previous[$itemId] ?? 0) + (int) $t->quantity;
                $previous[$itemId] = (int) $t->balance_after;

                if ($t->created_at->lt($start)) {
                    return;
                }

                if ($locationBatches !== null && ! isset($locationBatches[$t->batch_id])) {
                    return;
                }

                $common = [
                    'item_id' => $itemId,
                    'item_name' => $names[$itemId] ?? null,
                    'batch_id' => $t->batch_id,
                    'batch_number' => $t->inventoryBatch?->batch_number,
                    'transaction_id' => $t->id,
                    'occurred_at' => $t->created_at,
                ];

                if ((int) $t->balance_after !== $expected) {
                    $out->push($common + [
                        'kind' => 'balance_break',
                        'expected' => $expected,
                        'actual' => (int) $t->balance_after,
                        'difference' => (int) $t->balance_after - $expected,
                        'message' => "Balance after is {$t->balance_after} but the previous balance plus this movement is {$expected}.",
                    ]);
                }

                if ($t->balance_after < 0) {
                    $out->push($common + [
                        'kind' => 'negative_stock',
                        'expected' => 0,
                        'actual' => (int) $t->balance_after,
                        'difference' => (int) $t->balance_after,
                        'message' => "The ledger balance went negative ({$t->balance_after}).",
                    ]);
                }
            });

        // 2, 3, 4 and 5 (batch part): batches active or received in the period.
        InventoryBatches::query()
            ->whereIn('inventory_item_id', $itemIds())
            ->when($location, fn (Builder $q) => $q->where('storage_location_id', $location))
            ->where(fn (Builder $q) => $q
                ->whereBetween('received_at', [$startSql, $endSql])
                ->orWhereExists(fn ($e) => $e->selectRaw('1')
                    ->from('inventory_transactions as pt')
                    ->whereColumn('pt.batch_id', 'inventory_batches.id')
                    ->whereBetween('pt.created_at', [$startSql, $endSql])))
            ->orderBy('id')
            ->chunk(500, function (Collection $batches) use (&$out, $names, $end) {
                $ids = $batches->pluck('id');

                $sums = InventoryTransactions::query()
                    ->whereIn('batch_id', $ids)
                    ->selectRaw('batch_id, type, count(*) as entries, sum(quantity) as total')
                    ->groupBy('batch_id', 'type')
                    ->get()
                    ->groupBy('batch_id');

                $issued = StockIssuanceBatches::query()
                    ->whereIn('batch_id', $ids)
                    ->selectRaw('batch_id, sum(quantity) as total')
                    ->groupBy('batch_id')
                    ->pluck('total', 'batch_id');

                foreach ($batches as $batch) {
                    $byType = ($sums[$batch->id] ?? collect())->keyBy(fn ($r) => $r->type instanceof InventoryTransactionType ? $r->type->value : (int) $r->type);
                    $receiptRow = $byType->get(InventoryTransactionType::Receipt->value);
                    $receiptSum = (int) ($receiptRow->total ?? 0);
                    $adjSum = (int) ($byType->get(InventoryTransactionType::Adjustment->value)->total ?? 0);
                    $issSum = abs((int) ($byType->get(InventoryTransactionType::Issuance->value)->total ?? 0));
                    $pivotSum = (int) ($issued[$batch->id] ?? 0);

                    $common = [
                        'item_id' => (int) $batch->inventory_item_id,
                        'item_name' => $names[$batch->inventory_item_id] ?? null,
                        'batch_id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'transaction_id' => null,
                        'occurred_at' => $batch->received_at ?? $end,
                    ];

                    $expected = (int) $batch->received_qty + $adjSum - $issSum;
                    if ($expected !== (int) $batch->remaining_qty) {
                        $out->push($common + [
                            'kind' => 'batch_unbalanced',
                            'expected' => $expected,
                            'actual' => (int) $batch->remaining_qty,
                            'difference' => (int) $batch->remaining_qty - $expected,
                            'message' => "Received {$batch->received_qty}, adjusted {$adjSum}, issued {$issSum} gives {$expected}, but the batch holds {$batch->remaining_qty}.",
                        ]);
                    }

                    if ($receiptRow === null || $receiptSum !== (int) $batch->received_qty) {
                        $out->push($common + [
                            'kind' => 'receipt_mismatch',
                            'expected' => (int) $batch->received_qty,
                            'actual' => $receiptSum,
                            'difference' => $receiptSum - (int) $batch->received_qty,
                            'message' => $receiptRow === null
                                ? 'The batch has no Receipt transaction.'
                                : "Receipt transactions total {$receiptSum} but the batch received {$batch->received_qty}.",
                        ]);
                    }

                    if ($pivotSum !== $issSum) {
                        $out->push($common + [
                            'kind' => 'issuance_mismatch',
                            'expected' => $pivotSum,
                            'actual' => $issSum,
                            'difference' => $issSum - $pivotSum,
                            'message' => "Issuance records draw {$pivotSum} from this batch but the ledger shows {$issSum}.",
                        ]);
                    }

                    if ($batch->remaining_qty < 0) {
                        $out->push($common + [
                            'kind' => 'negative_stock',
                            'expected' => 0,
                            'actual' => (int) $batch->remaining_qty,
                            'difference' => (int) $batch->remaining_qty,
                            'message' => "The batch holds a negative quantity ({$batch->remaining_qty}).",
                        ]);
                    }
                }
            });

        return $out
            ->sortBy([['item_name', 'asc'], ['occurred_at', 'asc']])
            ->values();
    }

    /**
     * Items in scope: active or ever moved, narrowed by the filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<InventoryItems>
     */
    protected function itemScope(array $filters): Builder
    {
        $category = $filters['category'] ?? null;
        $location = $filters['location'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));

        return InventoryItems::query()
            ->where(fn (Builder $q) => $q
                ->where('inventory_items.is_active', true)
                ->orWhereExists(fn ($e) => $e->selectRaw('1')
                    ->from('inventory_transactions as it')
                    ->whereColumn('it.inventory_item_id', 'inventory_items.id')))
            ->when($category, fn (Builder $q) => $q->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('inventory_category_item as ic')
                ->whereColumn('ic.inventory_item_id', 'inventory_items.id')
                ->where('ic.inventory_category_id', $category)))
            ->when($location, fn (Builder $q) => $q->whereExists(fn ($e) => $e->selectRaw('1')
                ->from('inventory_batches as ib')
                ->whereColumn('ib.inventory_item_id', 'inventory_items.id')
                ->where('ib.storage_location_id', $location)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $s) => $s
                ->where('inventory_items.name', 'like', "%{$search}%")
                ->orWhere('inventory_items.item_code', 'like', "%{$search}%")));
    }

    /**
     * Base ledger query, limited to a location's batches when one is given.
     */
    protected function transactionQuery(?int $location, ?string $alias = null): \Illuminate\Database\Query\Builder
    {
        $column = ($alias ? $alias.'.' : '').'batch_id';

        return DB::table('inventory_transactions')
            ->when($location, fn ($q) => $q->whereIn($column, $this->locationBatchIds($location)));
    }

    /** @return Builder<InventoryBatches> */
    protected function locationBatchIds(int $location): Builder
    {
        return InventoryBatches::query()->where('storage_location_id', $location)->select('id');
    }

    protected function asOfToday(CarbonInterface $end): bool
    {
        return $end->toDateString() >= today()->toDateString();
    }
}
