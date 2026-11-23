<?php

namespace App\Livewire\Configurations;

use App\Livewire\Forms\StorageLocationsForm;
use App\Models\StorageLocations;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class StorageLocationForm extends Component
{
    public const TYPES = ['Shelf', 'Cabinet', 'Storage Rack', 'Storage Room'];

    public StorageLocationsForm $form;

    #[On('add-storage-location')]
    public function create(): void
    {
        $this->form->reset();
        $this->form->resetValidation();

        Flux::modal('storage-location-form')->show();
    }

    #[On('edit-storage-location')]
    public function edit(int $id): void
    {
        $this->form->resetValidation();
        $this->form->load(StorageLocations::findOrFail($id));

        Flux::modal('storage-location-form')->show();
    }

    public function save(): void
    {
        $isEditing = $this->form->storageLocation !== null;

        $isEditing ? $this->form->update() : $this->form->store();

        Flux::modal('storage-location-form')->close();

        Flux::toast(
            text: $isEditing ? 'Storage location updated successfully' : 'Storage location created successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('storage-location-saved');
    }

    public function render()
    {
        return view('livewire.configurations.storage-location-form', [
            'types' => self::TYPES,
        ]);
    }
}
