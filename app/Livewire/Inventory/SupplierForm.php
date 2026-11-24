<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\SuppliersForm;
use App\Models\Suppliers;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SupplierForm extends Component
{
    public SuppliersForm $form;

    #[On('add-supplier')]
    public function create(): void
    {
        $this->form->reset();
        $this->form->resetValidation();

        Flux::modal('supplier-form')->show();
    }

    #[On('edit-supplier')]
    public function edit(int $id): void
    {
        $this->form->resetValidation();
        $this->form->load(Suppliers::findOrFail($id));

        Flux::modal('supplier-form')->show();
    }

    public function save(): void
    {
        $isEditing = $this->form->supplier !== null;

        $isEditing ? $this->form->update() : $this->form->store();

        Flux::modal('supplier-form')->close();

        Flux::toast(
            text: $isEditing ? 'Supplier updated successfully' : 'Supplier created successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('supplier-saved');
    }

    public function render(): View
    {
        return view('livewire.inventory.supplier-form');
    }
}
