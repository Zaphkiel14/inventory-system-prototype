<?php

namespace App\Livewire\Monitoring;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransactions;
use App\Models\StockIssuances;
use App\Models\StockReceipts;
use App\Models\User;
use App\Services\Inventory\TransactionReferences;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The inventory ledger: every receipt, issuance and adjustment, searchable by
 * item, batch and the document that caused it, with totals and CSV export.
 *
 * Deferred: the page paints a skeleton first and loads the ledger in a request
 * fired straight after paint.
 *
 * @property-read array{0: CarbonInterface, 1: CarbonInterface}|null $period
 * @property-read array{entries: int, in: int, out: int, net: int} $totals
 * @property-read LengthAwarePaginator<int, InventoryTransactions> $transactions
 * @property-read EloquentCollection<int, User> $users
 */
#[Defer]
class TransactionHistory extends Component
{
    use WithPagination;

    public const RANGES = [
        'today' => 'Today',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        '365' => 'Last 12 months',
        'all' => 'All time',
        'custom' => 'Custom range',
    ];

    #[Url(except: '')]
    public string $search = '';

    /** '' or an InventoryTransactionType key (receipt, issuance, adjustment). */
    #[Url(except: '')]
    public string $type = '';

    /** '', 'in' or 'out'. */
    #[Url(except: '')]
    public string $direction = '';

    #[Url(except: '')]
    public string $user = '';

    #[Url(except: '30')]
    public string $range = '30';

    #[Url(except: null)]
    public ?string $from = null;

    #[Url(except: null)]
    public ?string $to = null;

    #[Url(except: 'desc')]
    public string $order = 'desc';

    public int $perPage = 25;

    public function mount(): void
    {
        $this->sanitizeFilters();
    }

    public function updated(string $property): void
    {
        if ($property === 'range') {
            if ($this->range === 'custom' && blank($this->from) && blank($this->to)) {
                $this->from = today()->subDays(29)->toDateString();
                $this->to = today()->toDateString();
            }

            if ($this->range !== 'custom') {
                $this->reset('from', 'to');
            }
        }

        $this->sanitizeFilters();
        $this->resetPage();
    }

    protected function sanitizeFilters(): void
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = '30';
        }

        if ($this->type !== '' && ! InventoryTransactionType::fromKey($this->type)) {
            $this->type = '';
        }

        if (! in_array($this->direction, ['', 'in', 'out'], true)) {
            $this->direction = '';
        }

        if ($this->user !== '' && ! ctype_digit($this->user)) {
            $this->user = '';
        }

        if (! in_array($this->order, ['asc', 'desc'], true)) {
            $this->order = 'desc';
        }
    }

    /**
     * The sort order as a query direction.
     *
     * @return 'asc'|'desc'
     */
    protected function direction(): string
    {
        return $this->order === 'asc' ? 'asc' : 'desc';
    }

    public function toggleOrder(): void
    {
        $this->order = $this->order === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'type', 'direction', 'user', 'range', 'from', 'to', 'order');
        $this->resetPage();
    }

    /* ------------------------------------------------------------------ */
    /* Query */
    /* ------------------------------------------------------------------ */

    /**
     * Start and end of the selected range, or null for "all time".
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    #[Computed]
    public function period(): ?array
    {
        if ($this->range === 'all') {
            return null;
        }

        if ($this->range === 'custom') {
            $start = $this->parseDate($this->from);
            $end = $this->parseDate($this->to);

            if (! $start && ! $end) {
                return null;
            }

            $start ??= Carbon::create(1970);
            $end ??= today();

            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            return [$start->startOfDay(), $end->endOfDay()];
        }

        $days = $this->range === 'today' ? 1 : (int) $this->range;

        return [today()->subDays($days - 1)->startOfDay(), today()->endOfDay()];
    }

    protected function parseDate(?string $value): ?CarbonInterface
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The filtered ledger query (no ordering), shared by the table, totals and export.
     *
     * @return Builder<InventoryTransactions>
     */
    protected function filtered(): Builder
    {
        $query = InventoryTransactions::query();

        if ($period = $this->period) {
            $query->whereBetween('inventory_transactions.created_at', $period);
        }

        if ($type = InventoryTransactionType::fromKey($this->type)) {
            $query->where('inventory_transactions.type', $type->value);
        }

        match ($this->direction) {
            'in' => $query->where('inventory_transactions.quantity', '>', 0),
            'out' => $query->where('inventory_transactions.quantity', '<', 0),
            default => null,
        };

        if ($this->user !== '') {
            $query->where('inventory_transactions.user_id', (int) $this->user);
        }

        if (($term = trim($this->search)) !== '') {
            $like = "%{$term}%";

            $query->where(fn (Builder $q) => $q
                ->whereHas('inventoryItem', fn (Builder $i) => $i->where('name', 'like', $like)->orWhere('item_code', 'like', $like))
                ->orWhereHas('inventoryBatch', fn (Builder $b) => $b->where('batch_number', 'like', $like)->orWhere('lot_number', 'like', $like))
                // The document behind the entry: delivery / PO no., issuance no., request no.
                ->orWhere(fn (Builder $r) => $r
                    ->where('reference_type', StockReceipts::class)
                    ->whereIn('reference_id', StockReceipts::query()
                        ->where('reference_number', 'like', $like)
                        ->orWhere('purchase_order_number', 'like', $like)
                        ->select('id')))
                ->orWhere(fn (Builder $r) => $r
                    ->where('reference_type', StockIssuances::class)
                    ->whereIn('reference_id', StockIssuances::query()
                        ->where('issuance_number', 'like', $like)
                        ->orWhereHas('requisition', fn (Builder $req) => $req->where('request_number', 'like', $like))
                        ->select('id'))));
        }

        return $query;
    }

    /**
     * One page of the filtered ledger. Items, batches, users and the source
     * documents are loaded in one query each; the row count comes from totals()
     * so the filtered set isn't counted twice.
     *
     * @return LengthAwarePaginator<int, InventoryTransactions>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        $page = $this->filtered()
            ->with(['inventoryItem:id,name,item_code,unit', 'inventoryBatch:id,batch_number,lot_number', 'user:id,name'])
            ->orderBy('inventory_transactions.created_at', $this->direction())
            ->orderBy('inventory_transactions.id', $this->direction())
            ->paginate($this->perPage, total: $this->totals['entries']);

        $references = $this->resolveReferences($page->getCollection());
        $page->getCollection()->each(fn (InventoryTransactions $t) => $t->setAttribute(
            'reference', $references[$t->reference_type.':'.$t->reference_id] ?? null,
        ));

        return $page;
    }

    /**
     * Totals for everything the filters match (not just the current page).
     *
     * @return array{entries: int, in: int, out: int, net: int}
     */
    #[Computed]
    public function totals(): array
    {
        $row = $this->filtered()
            ->selectRaw('count(*) as entries')
            ->selectRaw('coalesce(sum(case when quantity > 0 then quantity else 0 end), 0) as units_in')
            ->selectRaw('coalesce(sum(case when quantity < 0 then -quantity else 0 end), 0) as units_out')
            ->toBase()
            ->first();

        return [
            'entries' => (int) $row->entries,
            'in' => (int) $row->units_in,
            'out' => (int) $row->units_out,
            'net' => (int) $row->units_in - (int) $row->units_out,
        ];
    }

    /** @return EloquentCollection<int, User> */
    #[Computed]
    public function users(): EloquentCollection
    {
        return User::query()
            ->whereIn('id', InventoryTransactions::query()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Look up the document behind each entry, one query per document type.
     *
     * @param  Collection<int, InventoryTransactions>  $transactions
     * @return array<string, array{label: string, detail: ?string}>
     */
    protected function resolveReferences(Collection $transactions): array
    {
        return app(TransactionReferences::class)->resolve($transactions);
    }

    /* ------------------------------------------------------------------ */
    /* Export */
    /* ------------------------------------------------------------------ */

    public function export(): StreamedResponse
    {
        $filename = 'transactions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Date', 'Type', 'Item Code', 'Item', 'Batch No.', 'Lot No.', 'Quantity', 'Unit', 'Balance After', 'Reference', 'Details', 'Recorded By']);

            $this->filtered()
                ->with(['inventoryItem:id,name,item_code,unit', 'inventoryBatch:id,batch_number,lot_number', 'user:id,name'])
                ->orderBy('inventory_transactions.created_at', $this->direction())
                ->orderBy('inventory_transactions.id', $this->direction())
                ->chunk(500, function (Collection $chunk) use ($handle) {
                    $references = $this->resolveReferences($chunk);

                    foreach ($chunk as $t) {
                        $ref = $references[$t->reference_type.':'.$t->reference_id] ?? null;

                        fputcsv($handle, [
                            $t->created_at?->format('Y-m-d H:i:s'),
                            $t->type->label(),
                            $t->inventoryItem?->item_code,
                            $t->inventoryItem?->name,
                            $t->inventoryBatch?->batch_number,
                            $t->inventoryBatch?->lot_number,
                            $t->quantity,
                            $t->inventoryItem?->unit,
                            $t->balance_after,
                            $ref['label'] ?? null,
                            $ref['detail'] ?? null,
                            $t->user?->name,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.transaction-history');
    }

    public function render(): View
    {
        return view('livewire.monitoring.transaction-history');
    }
}
