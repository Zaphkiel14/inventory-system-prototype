<?php

namespace App\Livewire\Forms;

use App\Models\InventoryItems;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InventoryItemsForm extends Form
{
    public ?InventoryItems $inventoryItem = null;

    public array $category_ids = [];

    #[Validate('required|string|max:255')]
    public string $item_code = '';

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $unit = '';

    #[Validate('required|string|max:255')]
    public string $allocation_method = '';

    #[Validate('required|integer|min:0')]
    public int $minimum_stock = 0;

    #[Validate('required|integer|min:0')]
    public int $reorder_level = 0;

    #[Validate('required|boolean')]
    public bool $requires_expiry = false;

    #[Validate('required|boolean')]
    public bool $is_active = true;

    public function store(): void
    {
        $this->validate([
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:inventory_categories,id'],
        ]);

        DB::transaction(function () {
            $item = InventoryItems::create([
                'item_code' => $this->item_code,
                'name' => $this->name,
                'unit' => $this->unit,
                'allocation_method' => $this->allocation_method,
                'minimum_stock' => $this->minimum_stock,
                'reorder_level' => $this->reorder_level,
                'requires_expiry' => $this->requires_expiry,
                'is_active' => $this->is_active,
            ]);

            // Attach selected categories through the pivot table.
            $item->inventoryCategory()->sync($this->category_ids);
        });

        $this->reset();
    }

    public function update(): void
    {
        $this->validate([
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:inventory_categories,id'],
        ]);

        $this->inventoryItem->update([
            'item_code' => $this->item_code,
            'name' => $this->name,
            'unit' => $this->unit,
            'allocation_method' => $this->allocation_method,
            'minimum_stock' => $this->minimum_stock,
            'reorder_level' => $this->reorder_level,
            'requires_expiry' => $this->requires_expiry,
            'is_active' => $this->is_active,
        ]);

        // Update the item's category associations.
        $this->inventoryItem->inventoryCategory()->sync($this->category_ids);

        $this->reset();
    }
}
