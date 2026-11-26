<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryBatches;
use App\Models\StockReceipts;
use App\Support\InventoryCache;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batch cards: every batch with its item, receipt, location and expiry state.
 *
 * Deferred: the page paints a skeleton first and loads the batches in a request
 * fired straight after paint.
 *
 * @property-read LengthAwarePaginator<int, InventoryBatches> $batches
 * @property-read InventoryBatches|null $batchFilter
 * @property-read StockReceipts|null $receiptFilter
 * @property-read array<int, string> $categories
 */
#[Defer]
class Stocks extends Component
{
    use WithPagination;

    /**
     * Sort options shown in the UI => the column they order by.
     */
    public const SORTABLE = [
        'received_at' => ['label' => 'Received Date', 'column' => 'inventory_batches.received_at'],
        'purchase_order_number' => ['label' => 'PO No.', 'column' => 'stock_receipts.purchase_order_number'],
        'reference_number' => ['label' => 'Reference No.', 'column' => 'stock_receipts.reference_number'],
        'batch_number' => ['label' => 'Batch No.', 'column' => 'inventory_batches.batch_number'],
        'lot_number' => ['label' => 'Lot No.', 'column' => 'inventory_batches.lot_number'],
    ];

    /**
     * Expiration filter options => label.
     */
    public const EXPIRY_FILTERS = [
        '' => 'Any expiration',
        'expired' => 'Expired',
        '7' => 'Expires within 7 days',
        '30' => 'Expires within 30 days',
        '90' => 'Expires within 90 days',
        'none' => 'No expiration date',
        'custom' => 'Custom range',
    ];

    public string $search = '';

    /** Limits the batches to one stock receipt (linked from the Receiving page). */
    #[Url]
    public ?int $receipt = null;

    /** Limits the list to one batch (linked from the Adjustments page). */
    #[Url]
    public ?int $batch = null;

    /** Limits the list to items in one inventory category. */
    #[Url]
    public ?int $category = null;

    public string $expiry = '';

    public ?string $expiryFrom = null;

    public ?string $expiryTo = null;

    public string $sortBy = 'received_at';

    public string $sortDirection = 'desc';

    public int $perPage = 9;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        if (! array_key_exists($this->sortBy, self::SORTABLE)) {
            $this->sortBy = 'received_at';
        }

        $this->resetPage();
    }

    public function updatedExpiry(): void
    {
        if (! array_key_exists($this->expiry, self::EXPIRY_FILTERS)) {
            $this->expiry = '';
        }

        if ($this->expiry !== 'custom') {
            $this->reset('expiryFrom', 'expiryTo');
        }

        $this->resetPage();
    }

    public function updatedExpiryFrom(): void
    {
        $this->resetPage();
    }

    public function updatedExpiryTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'receipt', 'batch', 'category', 'expiry', 'expiryFrom', 'expiryTo');
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        if ($this->category !== null && $this->category <= 0) {
            $this->category = null;
        }

        $this->resetPage();
    }

    /** @return array<int, string> */
    #[Computed]
    public function categories(): array
    {
        return InventoryCache::categories()->pluck('name', 'id')->all();
    }

    public function clearBatch(): void
    {
        $this->reset('batch');
        $this->resetPage();
    }

    #[Computed]
    public function batchFilter(): ?InventoryBatches
    {
        return $this->batch ? InventoryBatches::with('inventoryItem')->find($this->batch) : null;
    }

    public function clearReceipt(): void
    {
        $this->reset('receipt');
        $this->resetPage();
    }

    #[Computed]
    public function receiptFilter(): ?StockReceipts
    {
        return $this->receipt ? StockReceipts::with('supplier:id,name')->find($this->receipt) : null;
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    /**
     * One page of batches. Pagination is in SQL; the item (with categories),
     * location, receipt and supplier are loaded in one query each.
     *
     * @return LengthAwarePaginator<int, InventoryBatches>
     */
    #[Computed]
    public function batches(): LengthAwarePaginator
    {
        $column = self::SORTABLE[$this->sortBy]['column'] ?? self::SORTABLE['received_at']['column'];
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return InventoryBatches::query()
            ->select('inventory_batches.*')
            ->join('stock_receipts', 'stock_receipts.id', '=', 'inventory_batches.stock_receipt_id')
            ->with([
                'inventoryItem.inventoryCategory' => fn ($q) => $q->orderBy('name'),
                'storageLocation:id,name',
                'stockReceipt:id,supplier_id,reference_number,purchase_order_number,is_donation',
                'stockReceipt.supplier:id,name',
            ])
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('inventory_batches.batch_number', 'like', $term)
                        ->orWhere('inventory_batches.lot_number', 'like', $term)
                        ->orWhere('stock_receipts.reference_number', 'like', $term)
                        ->orWhere('stock_receipts.purchase_order_number', 'like', $term)
                        ->orWhereHas('inventoryItem', fn ($q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term)
                            ->orWhereHas('inventoryCategory', fn ($c) => $c->where('name', 'like', $term)));
                });
            })
            ->when($this->receipt, fn ($query) => $query->where('inventory_batches.stock_receipt_id', $this->receipt))
            ->when($this->batch, fn ($query) => $query->where('inventory_batches.id', $this->batch))
            ->when($this->category, fn ($query) => $query->whereHas('inventoryItem.inventoryCategory', fn ($c) => $c
                ->where('inventory_categories.id', $this->category)))
            ->when($this->expiry !== '', fn ($query) => $this->applyExpiryFilter($query))
            ->orderBy($column, $direction)
            ->orderBy('inventory_batches.id', $direction)
            ->paginate($this->perPage);
    }

    /**
     * Kept separate from InventoryBatches::expired()/expiringWithin(): this page
     * lists depleted batches too, so it filters on the date alone.
     *
     * @param  Builder<InventoryBatches>  $query
     */
    protected function applyExpiryFilter(Builder $query): void
    {
        $column = 'inventory_batches.expiration_date';
        $today = today()->toDateString();

        // Raw-column bounds (index-friendly) as ">= day" / "< next day", which
        // match both "Y-m-d" and "Y-m-d 00:00:00" stored values like whereDate.
        match ($this->expiry) {
            'expired' => $query->whereNotNull($column)->where($column, '<', $today),
            '7', '30', '90' => $query->whereNotNull($column)
                ->where($column, '>=', $today)
                ->where($column, '<', today()->addDays((int) $this->expiry + 1)->toDateString()),
            'none' => $query->whereNull($column),
            'custom' => $query->whereNotNull($column)
                ->when($this->validDate($this->expiryFrom), fn ($q) => $q->where($column, '>=', Date::parse($this->expiryFrom)->toDateString()))
                ->when($this->validDate($this->expiryTo), fn ($q) => $q->where($column, '<', Date::parse($this->expiryTo)->addDay()->toDateString())),
            default => null,
        };
    }

    protected function validDate(?string $value): bool
    {
        return filled($value) && strtotime($value) !== false;
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.inventory.placeholders.stocks');
    }

    public function render(): View
    {
        return view('livewire.inventory.stocks');
    }
}
