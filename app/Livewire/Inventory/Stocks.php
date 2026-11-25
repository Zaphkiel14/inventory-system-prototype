<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryBatches;
use App\Models\StockReceipts;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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
        $this->reset('search', 'receipt', 'expiry', 'expiryFrom', 'expiryTo');
        $this->resetPage();
    }

    public function clearReceipt(): void
    {
        $this->reset('receipt');
        $this->resetPage();
    }

    #[Computed]
    public function receiptFilter(): ?StockReceipts
    {
        return $this->receipt ? StockReceipts::find($this->receipt) : null;
    }

    public function toggleSortDirection(): void
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    #[Computed]
    public function batches()
    {
        $column = self::SORTABLE[$this->sortBy]['column'] ?? self::SORTABLE['received_at']['column'];
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return InventoryBatches::query()
            ->select('inventory_batches.*')
            ->join('stock_receipts', 'stock_receipts.id', '=', 'inventory_batches.stock_receipt_id')
            ->with(['inventoryItem', 'storageLocation', 'stockReceipt.supplier'])
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('inventory_batches.batch_number', 'like', $term)
                        ->orWhere('inventory_batches.lot_number', 'like', $term)
                        ->orWhere('stock_receipts.reference_number', 'like', $term)
                        ->orWhere('stock_receipts.purchase_order_number', 'like', $term)
                        ->orWhereHas('inventoryItem', fn ($q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            ->when($this->receipt, fn ($query) => $query->where('inventory_batches.stock_receipt_id', $this->receipt))
            ->when($this->expiry !== '', fn ($query) => $this->applyExpiryFilter($query))
            ->orderBy($column, $direction)
            ->orderBy('inventory_batches.id', $direction)
            ->paginate($this->perPage);
    }

    protected function applyExpiryFilter($query): void
    {
        $column = 'inventory_batches.expiration_date';
        $today = today()->toDateString();

        match ($this->expiry) {
            'expired' => $query->whereNotNull($column)->whereDate($column, '<', $today),
            '7', '30', '90' => $query->whereNotNull($column)
                ->whereDate($column, '>=', $today)
                ->whereDate($column, '<=', today()->addDays((int) $this->expiry)->toDateString()),
            'none' => $query->whereNull($column),
            'custom' => $query->whereNotNull($column)
                ->when($this->validDate($this->expiryFrom), fn ($q) => $q->whereDate($column, '>=', $this->expiryFrom))
                ->when($this->validDate($this->expiryTo), fn ($q) => $q->whereDate($column, '<=', $this->expiryTo)),
            default => null,
        };
    }

    protected function validDate(?string $value): bool
    {
        return filled($value) && strtotime($value) !== false;
    }

    public function render()
    {
        return view('livewire.inventory.stocks');
    }
}
