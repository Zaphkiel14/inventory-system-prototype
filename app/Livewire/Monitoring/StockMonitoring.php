<?php

namespace App\Livewire\Monitoring;

use App\Enums\StockStatus;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Services\Inventory\StockFigures;
use App\Support\InventoryCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Item-level stock monitoring: usable, committed and free stock against each
 * item's thresholds, how long it will last at the recent usage rate, and a
 * drill-down into the item's batches in the order issuance will draw them.
 *
 * Deferred: the page paints a skeleton first and loads the (heavy) rows in a
 * request fired straight after paint.
 *
 * @property-read Collection<int, InventoryItems> $rows
 * @property-read array<int, array{minimum_stock: int, reorder_level: int, available_qty: int, committed_qty: int, usable_batches: int, next_expiry: ?string, expired_qty: int, issued_recent: int, last_received_at: ?string, value: float}> $figures
 */
#[Defer]
class StockMonitoring extends Component
{
    use WithPagination;

    /** Days of issuance history used for the usage rate and days of cover. */
    public const USAGE_DAYS = StockFigures::USAGE_DAYS;

    /** Days of balance history shown in the item drill-down (StockDetail). */
    public const TREND_DAYS = StockFigures::TREND_DAYS;

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

    /* ------------------------------------------------------------------ */
    /* Item rows */
    /* ------------------------------------------------------------------ */

    /**
     * Stock figures for every active item, keyed by item id. This is the
     * heavy part (one aggregate subquery per figure), so it is cached under
     * the ledger version: any stock movement, batch or item change rebuilds it.
     * Plain arrays only (the cache refuses objects).
     *
     * @return array<int, array{minimum_stock: int, reorder_level: int, available_qty: int, committed_qty: int, usable_batches: int, next_expiry: ?string, expired_qty: int, issued_recent: int, last_received_at: ?string, value: float}>
     */
    #[Computed]
    public function figures(): array
    {
        return app(StockFigures::class)->all();
    }

    /**
     * Active items matching the search and category, by name, with their
     * stock figures and status (unpaginated; status and sort are applied in
     * items(), since the status is derived from the figures).
     *
     * @return Collection<int, InventoryItems>
     */
    #[Computed]
    public function rows(): Collection
    {
        $figures = $this->figures;
        $service = app(StockFigures::class);
        $term = trim($this->search);

        return InventoryItems::query()
            ->where('is_active', true)
            ->with('inventoryCategory:inventory_categories.id,inventory_categories.name')
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('item_code', 'like', "%{$term}%")))
            ->when($this->category !== '', fn (Builder $q) => $q
                ->whereHas('inventoryCategory', fn (Builder $c) => $c->whereKey((int) $this->category)))
            ->orderBy('name')
            ->get()
            ->each(fn (InventoryItems $item) => $service->decorate($item, $figures[$item->id] ?? null));
    }

    /**
     * Item count per status across all active items (ignores the filters).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $counts = array_fill_keys(array_column(StockStatus::cases(), 'value'), 0);

        foreach ($this->figures as $f) {
            $counts[StockStatus::for($f['available_qty'], $f['minimum_stock'], $f['reorder_level'])->value]++;
        }

        return $counts;
    }

    /** @return LengthAwarePaginator<int, InventoryItems> */
    #[Computed]
    public function items(): LengthAwarePaginator
    {
        // Search and category are already applied in SQL by rows().
        $filtered = $this->rows
            ->when($this->status === 'attention', fn (Collection $c) => $c->filter(fn (InventoryItems $i) => $i->stock_status->needsReorder()))
            ->when(! in_array($this->status, ['', 'attention'], true), fn (Collection $c) => $c->filter(fn (InventoryItems $i) => $i->stock_status->value === $this->status));

        $sorted = match ($this->sort) {
            'name' => $filtered->sortBy(fn (InventoryItems $i) => mb_strtolower($i->name)),
            'stock' => $filtered->sortBy([['available', 'asc'], ['name', 'asc']]),
            // Items with no recent usage have no meaningful cover; keep them last.
            'cover' => $filtered->sortBy([
                fn (InventoryItems $a, InventoryItems $b) => ($a->cover_days === null) <=> ($b->cover_days === null),
                fn (InventoryItems $a, InventoryItems $b) => $a->cover_days <=> $b->cover_days,
                fn (InventoryItems $a, InventoryItems $b) => $a->name <=> $b->name,
            ]),
            default => $filtered->sortBy([
                fn (InventoryItems $a, InventoryItems $b) => $a->stock_status->urgency() <=> $b->stock_status->urgency(),
                fn (InventoryItems $a, InventoryItems $b) => ($a->available / max(1, $a->reorder_level)) <=> ($b->available / max(1, $b->reorder_level)),
                fn (InventoryItems $a, InventoryItems $b) => $a->name <=> $b->name,
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

    /** @return EloquentCollection<int, InventoryCategories> */
    #[Computed]
    public function categories(): EloquentCollection
    {
        return InventoryCache::categories()->where('is_active', true)->values();
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.stock-monitoring');
    }

    public function render(): View
    {
        return view('livewire.monitoring.stock-monitoring');
    }
}
