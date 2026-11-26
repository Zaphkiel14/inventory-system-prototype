<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\InventoryItemsForm;
use App\Support\InventoryCache;
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
        return InventoryCache::categories()->where('is_active', true)->values();
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
