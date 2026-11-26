<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\InventoryItemsForm;
use App\Models\InventoryItems;
use App\Support\InventoryCache;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ItemEditForm extends Component
{
    public InventoryItemsForm $form;

    #[On('edit-inventory-item')]
    public function edit(int $itemId): void
    {
        $item = InventoryItems::findOrFail($itemId);

        $this->form->resetValidation();
        $this->form->setItem($item);

        Flux::modal('edit-item')->show();
    }

    #[Computed]
    public function categories()
    {
        return InventoryCache::categories()->where('is_active', true)->values();
    }

    public function save(): void
    {
        $this->form->update();

        Flux::modal('edit-item')->close();

        Flux::toast(
            text: 'Inventory item updated successfully.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('inventory-item-updated');
    }

    public function render()
    {
        return view('livewire.inventory.item-edit-form');
    }
}
