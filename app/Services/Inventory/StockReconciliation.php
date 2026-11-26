<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Support\InventoryCache;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Read-only audit of the stock ledger: does opening + movements = expected, and
 * does expected match what the batches (or the last ledger balance) say?
 * Never writes batches or transactions.
 *
 * @phpstan-type Filters array{category?: ?int, location?: ?int, search?: ?string, status?: ?string}
 * @phpstan-type ReconciliationRow array{item_id: int, name: string, unit: string, opening: int, received: int, issued: int, adjusted: int, expected: int, recorded: int, actual: ?int, actual_source: string, variance: ?int, status: string, exception_count: int, exception_kinds: array<int>}
 */
class StockReconciliation
{
    /** @var array<string, Collection<int, array<string, mixed>>> */
    protected array $exceptionCache = [];

    /**
     * Item names per item scope, filled by summary() so the exception pass
     * does not query the same items again.
     *
     * @var array<string, Collection<int, string>>
     */
    protected array $nameCache = [];

    /**
     * One row per item with opening, movements, expected vs actual and variance.
     *
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ReconciliationRow>
     */
    public function summary(array $period, array $filters = []): Collection
    {
        // Rows are plain arrays, so they cache as-is under the ledger version.
        return collect(InventoryCache::ledger(
            'reconciliation:summary',
            [...$this->periodKey($period), $filters, today()->toDateString()],
            fn (): array => $this->buildSummary($period, $filters)->all(),
        ));
    }

    /**
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @param  array<string, mixed>  $filters
     * @return Collection<int, ReconciliationRow>
     */
    protected function buildSummary(array $period, array $filters): Collection
    {
        [$start, $end] = $period;
        $startSql = $start->format('Y-m-d H:i:s');
        $endSql = $end->format('Y-m-d H:i:s');
        $receipt = (string) InventoryTransactionType::Receipt->value;
        $issuance = (string) InventoryTransactionType::Issuance->value;
        $adjustment = (string) InventoryTransactionType::Adjustment->value;
        $location = $filters['location'] ?? null;

        $items = $this->itemScope($filters)->orderBy('name')->get(['inventory_items.id', 'inventory_items.name', 'inventory_items.unit']);
        $this->nameCache[$this->scopeKey($filters)] = $items->pluck('name', 'id');

        $ledger = $this->transactionQuery($location)
            ->selectRaw('inventory_item_id')
            ->selectRaw('coalesce(sum(case when created_at < ? then quantity else 0 end), 0) as opening', [$startSql])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as received', [$startSql, $endSql, $receipt])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as issued', [$startSql, $endSql, $issuance])
            ->selectRaw('coalesce(sum(case when created_at between ? and ? and type = ? then quantity else 0 end), 0) as adjusted', [$startSql, $endSql, $adjustment])
            ->groupBy('inventory_item_id')
            ->get()
            ->keyBy('inventory_item_id');

        $recorded = $this->lastBalancePerItem(null, '<=', $endSql, $location);

        $asOfToday = $this->asOfToday($end);

        $batches = $asOfToday
            ? InventoryBatches::query()
                ->when($location, fn (Builder $q) => $q->where('storage_location_id', $location))
                ->selectRaw('inventory_item_id, coalesce(sum(remaining_qty), 0) as total')
                ->groupBy('inventory_item_id')
                ->pluck('total', 'inventory_item_id')
            : collect();

        $exceptionsByItem = $this->exceptions($period, $filters)->groupBy('item_id');

        $rows = $items->map(/** @return ReconciliationRow */ function (InventoryItems $item) use ($ledger, $recorded, $batches, $asOfToday, $location, $exceptionsByItem): array {
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
        $params = [...$this->periodKey($period), $filters['category'] ?? null, $filters['location'] ?? null, $filters['search'] ?? null];
        $key = md5(serialize($params));
        $timezone = config('app.timezone');

        // Cached under the ledger version with occurred_at as a Unix timestamp
        // (the cache refuses objects), turned back into a date on the way out.
        if (! isset($this->exceptionCache[$key])) {
            $cached = InventoryCache::ledger(
                'reconciliation:exceptions',
                $params,
                fn (): array => $this->buildExceptions($period, $filters)
                    ->map(fn (array $e) => $this->withOccurredAt($e, $e['occurred_at']->getTimestamp()))
                    ->all(),
            );

            // A timestamp rebuilds much faster than parsing a date string per exception.
            $this->exceptionCache[$key] = collect($cached)
                ->map(fn (array $e) => $this->withOccurredAt($e, Date::createFromTimestamp($e['occurred_at'], $timezone)))
                ->values();
        }

        return $this->exceptionCache[$key];
    }

    /**
     * The exception with occurred_at replaced, keeping its key order.
     *
     * @param  array<string, mixed>  $exception
     * @return array<string, mixed>
     */
    protected function withOccurredAt(array $exception, mixed $occurredAt): array
    {
        $exception['occurred_at'] = $occurredAt;

        return $exception;
    }

    /**
     * @param  array{0: CarbonInterface, 1: CarbonInterface}  $period
     * @return array{0: string, 1: string}
     */
    protected function periodKey(array $period): array
    {
        return [$period[0]->toIso8601String(), $period[1]->toIso8601String()];
    }

    /**
     * @param  Collection<int, ReconciliationRow>  $rows
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
        $names = $this->nameCache[$this->scopeKey($filters)]
            ?? $this->itemScope($filters)->pluck('inventory_items.name', 'inventory_items.id');
        $locationBatches = $location ? array_flip($this->locationBatchIds($location)->pluck('id')->all()) : null;

        // 1 and 5 (ledger part): each row is checked against the item's previous
        // balance_after in (item, created_at, id) order. The previous balance
        // going into the period is seeded from each item's last row before
        // $start, so only the period itself is streamed (plain rows, no models).
        $transaction = new InventoryTransactions;
        $previous = $this->lastBalancePerItem($itemIds(), '<', $startSql)->all();

        $ledger = DB::table('inventory_transactions as t')
            ->leftJoin('inventory_batches as b', 'b.id', '=', 't.batch_id')
            ->whereIn('t.inventory_item_id', $itemIds())
            ->where('t.created_at', '>=', $startSql)
            ->where('t.created_at', '<=', $endSql)
            ->orderBy('t.inventory_item_id')->orderBy('t.created_at')->orderBy('t.id')
            ->select(['t.id', 't.inventory_item_id', 't.batch_id', 't.quantity', 't.balance_after', 't.created_at', 'b.batch_number'])
            ->cursor();

        foreach ($ledger as $t) {
            $itemId = (int) $t->inventory_item_id;
            $expected = ($previous[$itemId] ?? 0) + (int) $t->quantity;
            $previous[$itemId] = (int) $t->balance_after;

            // Cast created_at through the model's own date cast, as the hydrated model did.
            $createdAt = $transaction->setRawAttributes(['created_at' => $t->created_at])->created_at;

            if ($locationBatches !== null && ! isset($locationBatches[$t->batch_id])) {
                continue;
            }

            $common = [
                'item_id' => $itemId,
                'item_name' => $names[$itemId] ?? null,
                'batch_id' => $t->batch_id,
                'batch_number' => $t->batch_number,
                'transaction_id' => $t->id,
                'occurred_at' => $createdAt,
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
        }

        // 2, 3, 4 and 5 (batch part): batches active or received in the period.
        $batchModel = new InventoryBatches;

        InventoryBatches::query()
            ->whereIn('inventory_batches.inventory_item_id', $itemIds())
            ->when($location, fn (Builder $q) => $q->where('inventory_batches.storage_location_id', $location))
            ->where(fn (Builder $q) => $q
                ->whereBetween('inventory_batches.received_at', [$startSql, $endSql])
                ->orWhereExists(fn ($e) => $e->selectRaw('1')
                    ->from('inventory_transactions as pt')
                    ->whereColumn('pt.batch_id', 'inventory_batches.id')
                    ->whereBetween('pt.created_at', [$startSql, $endSql])))
            ->toBase()
            // Per-batch ledger and issuance totals joined in, so this is one
            // streamed query however many batches there are.
            ->leftJoinSub(
                DB::table('inventory_transactions')
                    ->selectRaw('batch_id')
                    ->selectRaw('sum(case when type = ? then 1 else 0 end) as receipt_entries', [$receipt = (string) InventoryTransactionType::Receipt->value])
                    ->selectRaw('coalesce(sum(case when type = ? then quantity else 0 end), 0) as receipt_total', [$receipt])
                    ->selectRaw('coalesce(sum(case when type = ? then quantity else 0 end), 0) as adjusted_total', [(string) InventoryTransactionType::Adjustment->value])
                    ->selectRaw('coalesce(sum(case when type = ? then quantity else 0 end), 0) as issued_total', [(string) InventoryTransactionType::Issuance->value])
                    ->whereNotNull('batch_id')
                    ->groupBy('batch_id'),
                'tx', 'tx.batch_id', '=', 'inventory_batches.id',
            )
            ->leftJoinSub(
                DB::table('stock_issuance_batches')
                    ->selectRaw('batch_id, sum(quantity) as total')
                    ->groupBy('batch_id'),
                'sib', 'sib.batch_id', '=', 'inventory_batches.id',
            )
            ->select([
                'inventory_batches.id', 'inventory_batches.inventory_item_id', 'inventory_batches.batch_number',
                'inventory_batches.received_qty', 'inventory_batches.remaining_qty', 'inventory_batches.received_at',
                'tx.receipt_entries', 'tx.receipt_total', 'tx.adjusted_total', 'tx.issued_total', 'sib.total as pivot_total',
            ])
            ->orderBy('inventory_batches.id')
            ->cursor()
            ->each(function (object $batch) use (&$out, $names, $batchModel): void {
                $receiptSum = (int) ($batch->receipt_total ?? 0);
                $hasReceipt = (int) ($batch->receipt_entries ?? 0) > 0;
                $adjSum = (int) ($batch->adjusted_total ?? 0);
                $issSum = abs((int) ($batch->issued_total ?? 0));
                $pivotSum = (int) ($batch->pivot_total ?? 0);

                // Cast received_at through the model's own cast, as the hydrated model did.
                $receivedAt = $batchModel->setRawAttributes(['received_at' => $batch->received_at])->received_at;

                $common = [
                    'item_id' => (int) $batch->inventory_item_id,
                    'item_name' => $names[$batch->inventory_item_id] ?? null,
                    'batch_id' => (int) $batch->id,
                    'batch_number' => $batch->batch_number,
                    'transaction_id' => null,
                    'occurred_at' => $receivedAt,
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

                if (! $hasReceipt || $receiptSum !== (int) $batch->received_qty) {
                    $out->push($common + [
                        'kind' => 'receipt_mismatch',
                        'expected' => (int) $batch->received_qty,
                        'actual' => $receiptSum,
                        'difference' => $receiptSum - (int) $batch->received_qty,
                        'message' => ! $hasReceipt
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
     * Cache key for the item scope (the filters itemScope() reads).
     *
     * @param  array<string, mixed>  $filters
     */
    protected function scopeKey(array $filters): string
    {
        return md5(serialize([$filters['category'] ?? null, $filters['location'] ?? null, trim((string) ($filters['search'] ?? ''))]));
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

    /**
     * Each item's last ledger row (by created_at, then id) whose created_at
     * compares $operator $bound, keyed by item id. One window-function query.
     * With a location, only rows of that location's batches are considered.
     *
     * @param  Builder<InventoryItems>|null  $itemIds  subquery selecting item ids, or null for all items
     * @return Collection<int, int> balance_after keyed by item id
     */
    protected function lastBalancePerItem(?Builder $itemIds, string $operator, string $bound, ?int $location = null): Collection
    {
        $ranked = $this->transactionQuery($location)
            ->when($itemIds, fn ($q) => $q->whereIn('inventory_item_id', $itemIds))
            ->where('created_at', $operator, $bound)
            ->select(['inventory_item_id', 'balance_after'])
            ->selectRaw('row_number() over (partition by inventory_item_id order by created_at desc, id desc) as rn');

        return DB::query()->fromSub($ranked, 'r')
            ->where('rn', 1)
            ->get(['inventory_item_id', 'balance_after'])
            ->mapWithKeys(fn (stdClass $row) => [(int) $row->inventory_item_id => (int) $row->balance_after]);
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
