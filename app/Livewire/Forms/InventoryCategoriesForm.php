<?php

namespace App\Livewire\Forms;

use App\Models\InventoryCategories;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InventoryCategoriesForm extends Form
{
    public ?InventoryCategories $inventoryCategory;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public ?string $description = null;

    #[Validate('required|boolean')]
    public bool $is_active = true;

    public function store()
    {
        $this->validate();

        InventoryCategories::create([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $this->reset();
    }

    public function update()
    {
        $this->validate();

        $this->inventoryCategory->update([
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);
    }
}
