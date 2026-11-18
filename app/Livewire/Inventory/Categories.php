<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryCategories;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Categories extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public function sort(string $column): void
    {
        $allowedColumns = [
            'name',
            'description',
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

    #[On('inventory-category-created')]
    public function refreshItems(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getCategoriesProperty()
    {
        return InventoryCategories::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.inventory.categories');
    }
}
