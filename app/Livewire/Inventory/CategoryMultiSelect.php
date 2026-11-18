<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryCategories;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class CategoryMultiSelect extends Component
{
    #[Modelable]
    public array $value = [];

    public string $search = '';

    #[Computed]
    public function categories()
    {
        return InventoryCategories::query()
            ->where('is_active', true)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function selectedCategories()
    {
        return InventoryCategories::query()
            ->whereIn('id', $this->value)
            ->orderBy('name')
            ->get();
    }

    public function toggleCategory(int $categoryId): void
    {
        $category = InventoryCategories::query()
            ->where('is_active', true)
            ->findOrFail($categoryId);

        $selectedIds = array_map('intval', $this->value);

        if (in_array($category->id, $selectedIds, true)) {
            $this->value = array_values(
                array_filter(
                    $selectedIds,
                    fn ($id) => $id !== $category->id
                )
            );
        } else {
            $this->value[] = $category->id;
        }
    }

    public function removeCategory(int $categoryId): void
    {
        $this->value = array_values(
            array_filter(
                array_map('intval', $this->value),
                fn ($id) => $id !== $categoryId
            )
        );
    }

    public function render()
    {
        return view('livewire.inventory.category-multi-select');
    }
}
