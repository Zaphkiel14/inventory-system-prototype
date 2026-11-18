<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\InventoryItemsForm;
use App\Models\InventoryCategories;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ItemCreateForm extends Component
{
    public InventoryItemsForm $form;

    public function mount(): void
    {
        $this->form = new InventoryItemsForm($this, 'form');
    }

    #[Computed]
    public function categories()
    {
        return InventoryCategories::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $this->form->store();

        Flux::modal('create-item')->close();

        Flux::toast(
            text: 'Inventory item created successfully.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('inventory-item-created');
    }

    public function render()
    {
        return view('livewire.inventory.item-create-form');
    }
}
