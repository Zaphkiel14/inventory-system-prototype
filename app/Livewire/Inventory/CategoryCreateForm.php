<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\InventoryCategoriesForm;
use Flux\Flux;
use Livewire\Component;

class CategoryCreateForm extends Component
{
    public InventoryCategoriesForm $form;

    public function mount(): void
    {
        $this->form = new InventoryCategoriesForm($this, 'form');
    }

    public function save(): void
    {
        $this->form->store();

        Flux::modal('create-category')->close();

        Flux::toast(
            text: 'Inventory category created successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('inventory-category-created');
    }

    public function render()
    {
        return view('livewire.inventory.category-create-form');
    }
}
