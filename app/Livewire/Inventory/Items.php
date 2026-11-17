<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItems;
use Livewire\Component;
use Livewire\WithPagination;
class Items extends Component
{

    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 10;
     public function sort(string $column): void
    {
        $allowedColumns = [
            'code',
            'name',
            'unit',
            'allocation_method',
            'requires_expiry',
            'minimum_stock',
            'reorder_level',
            'is_active',
        ];

        if (! in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getItemsProperty()
    {
        return InventoryItems::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('code', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.inventory.items');
    }
}
