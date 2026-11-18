<?php

namespace App\Livewire\Forms;

use App\Models\InventoryItems;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InventoryItemsForm extends Form
{
    public ?InventoryItems $inventoryItem;

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

    public function store()
    {
        $this->validate();

        InventoryItems::create([
            'item_code' => $this->item_code,
            'name' => $this->name,
            'unit' => $this->unit,
            'allocation_method' => $this->allocation_method,
            'requires_expiry' => $this->requires_expiry,
            'minimum_stock' => $this->minimum_stock,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
    }

    public function update()
    {
        $this->validate();

        $this->inventoryItem->update([
            'item_code' => $this->item_code,
            'name' => $this->name,
            'unit' => $this->unit,
            'allocation_method' => $this->allocation_method,
            'requires_expiry' => $this->requires_expiry,
            'minimum_stock' => $this->minimum_stock,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
    }
}
