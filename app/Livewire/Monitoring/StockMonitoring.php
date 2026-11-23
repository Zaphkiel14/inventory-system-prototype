<?php

namespace App\Livewire\Monitoring;

use App\Enums\InventoryTransactionType;
use App\Enums\StockStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Services\Inventory\BatchAllocator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Item-level stock monitoring: usable, committed and free stock against each
 * item's thresholds, how long it will last at the recent usage rate, and a
 * drill-down into the item's batches in the order issuance will draw them.
 */
class StockMonitoring extends Component
{
    use WithPagination;

    /** Days of issuance history used for the usage rate and days of cover. */
    public const USAGE_DAYS = 90;

    /** Days of balance history shown in the item drill-down. */
    public const TREND_DAYS = 90;

    public const SORTS = [
        'urgency' => 'Most urgent',
        'cover' => 'Fewest days of cover',
        'stock' => 'Lowest stock',
        'name' => 'Name',
    ];

    #[Url(except: '')]
    public string $search = '';

    /** '' (all), 'attention' (anything not healthy) or a StockStatus value. */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: 'urgency')]
    public string $sort = 'urgency';

    #[Url(except: null)]
    public ?int $expanded = null;

    public int $perPage = 15;

    public function mount(): void
    {
        $this->sanitizeFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category', 'sort'], true)) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    protected function sanitizeFilters(): void
    {
        $statuses = ['', 'attention', ...array_column(StockStatus::cases(), 'value')];

        if (! in_array($this->status, $statuses, true)) {
            $this->status = '';
        }

        if (! array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'urgency';
        }

        if ($this->category !== '' && ! ctype_digit($this->category)) {
            $this->category = '';
        }
    }

    public function filterStatus(string $status): void
    {
        $this->status = $this->status === $status ? '' : $status;
        $this->sanitizeFilters();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'category', 'sort');
        $this->resetPage();
    }

    public function toggle(int $itemId): void
    {
        $this->expanded = $this->expanded === $itemId ? null : $itemId;
    }

    /* ------------------------------------------------------------------ */
    /* Item rows */
    /* ------------------------------------------------------------------ */

    /**
     * Every active item with its stock figures and status (unpaginated).
     *
     * @return Collection<int, InventoryItems>
     */
    #[Computed]
    public function rows(): Collection
    {
        $values = InventoryBatches::query()->usable()
            ->groupBy('inventory_item_id')
            ->selectRaw('inventory_item_id, sum(remaining_qty * unit_cost) as value')
            ->pluck('value', 'inventory_item_id');

        return InventoryItems::query()
            ->where('is_active', true)
            ->with('inventoryCategory:inventory_categories.id,inventory_categories.name')
            ->withAvailableQty()
            ->withCommittedQty()
            ->withCount(['inventoryBatches as usable_batches' => fn (Builder $q) => $q->usable()])
            ->withMin(['inventoryBatches as next_expiry' => fn (Builder $q) => $q->usable()], 'expiration_date')
            ->withSum(['inventoryBatches as expired_qty' => fn (Builder $q) => $q->expired()], 'remaining_qty')
            ->withSum(['inventoryTransactions as issued_recent' => fn (Builder $q) => $q
                ->where('type', InventoryTransactionType::Issuance)
                ->where('created_at', '>=', now()->subDays(self::USAGE_DAYS))], 'quantity')
            ->withMax('inventoryBatches as last_received_at', 'received_at')
            ->orderBy('name')
            ->get()
            ->each(function (InventoryItems $item) use ($values) {
                $item->available = (int) $item->available_qty;
                $item->committed = min((int) $item->committed_qty, $item->available);
                $item->free = $item->available - $item->committed;
                $item->expired = (int) $item->expired_qty;
                $item->value = (float) ($values[$item->id] ?? 0);
                $item->stock_status = StockStatus::for($item->available, $item->minimum_stock, $item->reorder_level);
                $item->daily_usage = (float) abs((int) $item->issued_recent) / self::USAGE_DAYS;
                $item->cover_days = $item->daily_usage > 0 ? (int) floor($item->free / $item->daily_usage) : null;
            });
    }

    /**
     * Item count per status across all active items.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $counts = array_fill_keys(array_column(StockStatus::cases(), 'value'), 0);

        foreach ($this->rows as $item) {
            $counts[$item->stock_status->value]++;
        }

        return $counts;
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $term = mb_strtolower(trim($this->search));

        $filtered = $this->rows
            ->when($term !== '', fn (Collection $c) => $c->filter(fn (InventoryItems $i) => str_contains(mb_strtolower($i->name), $term)
                || str_contains(mb_strtolower($i->item_code), $term)))
            ->when($this->category !== '', fn (Collection $c) => $c->filter(fn (InventoryItems $i) => $i->inventoryCategory->contains('id', (int) $this->category)))
            ->when($this->status === 'attention', fn (Collection $c) => $c->filter(fn (InventoryItems $i) => $i->stock_status->needsReorder()))
            ->when(! in_array($this->status, ['', 'attention'], true), fn (Collection $c) => $c->filter(fn (InventoryItems $i) => $i->stock_status->value === $this->status));

        $sorted = match ($this->sort) {
            'name' => $filtered->sortBy(fn (InventoryItems $i) => mb_strtolower($i->name)),
            'stock' => $filtered->sortBy([['available', 'asc'], ['name', 'asc']]),
            // Items with no recent usage have no meaningful cover; keep them last.
            'cover' => $filtered->sortBy([
                fn ($a, $b) => ($a->cover_days === null) <=> ($b->cover_days === null),
                fn ($a, $b) => $a->cover_days <=> $b->cover_days,
                fn ($a, $b) => $a->name <=> $b->name,
            ]),
            default => $filtered->sortBy([
                fn ($a, $b) => $a->stock_status->urgency() <=> $b->stock_status->urgency(),
                fn ($a, $b) => ($a->available / max(1, $a->reorder_level)) <=> ($b->available / max(1, $b->reorder_level)),
                fn ($a, $b) => $a->name <=> $b->name,
            ]),
        };

        $page = $this->getPage();

        return new LengthAwarePaginator(
            $sorted->values()->forPage($page, $this->perPage),
            $sorted->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    #[Computed]
    public function categories(): Collection
    {
        return InventoryCategories::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /* ------------------------------------------------------------------ */
    /* Drill-down */
    /* ------------------------------------------------------------------ */

    /**
     * Batches, balance history and recent movement for the expanded item.
     */
    #[Computed]
    public function detail(): ?array
    {
        $item = $this->expanded ? $this->rows->firstWhere('id', $this->expanded) : null;

        if (! $item) {
            return null;
        }

        $allocator = app(BatchAllocator::class);

        // Batches in the exact order issuance will draw from them, with the
        // stock already promised to approved requests reserved from the front.
        $queue = $allocator->queue($item)->with('storageLocation')->get();
        $reserved = $allocator->allocate($item, $item->committed)['allocations']
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

    public function render()
    {
        return view('livewire.monitoring.stock-monitoring');
    }
}
