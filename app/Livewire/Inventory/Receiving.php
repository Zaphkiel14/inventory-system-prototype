<?php

namespace App\Livewire\Inventory;

use App\Models\StockReceipts;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Receiving extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    #[On('stock-received')]
    public function refreshReceipts(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function receipts()
    {
        return StockReceipts::query()
            ->with('supplier')
            ->withCount('inventoryBatches')
            ->withSum('inventoryBatches', 'received_qty')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('purchase_order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('received_date')
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.inventory.receiving');
    }
}
