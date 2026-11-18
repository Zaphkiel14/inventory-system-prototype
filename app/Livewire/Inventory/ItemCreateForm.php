<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\InventoryItemForm;
use Flux\Flux;
use Livewire\Component;

class ItemCreateForm extends Component
{
    public InventoryItemForm $form;

    public function mount(): void
    {
        $this->form = new InventoryItemForm($this, 'form');
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
