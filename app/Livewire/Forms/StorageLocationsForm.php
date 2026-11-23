<?php

namespace App\Livewire\Forms;

use App\Models\StorageLocations;
use Livewire\Attributes\Validate;
use Livewire\Form;

class StorageLocationsForm extends Form
{
    public ?StorageLocations $storageLocation = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255')]
    public string $type = '';

    public function load(StorageLocations $location): void
    {
        $this->storageLocation = $location;
        $this->name = $location->name;
        $this->type = $location->type;
    }

    public function store(): void
    {
        $this->validate();

        StorageLocations::create([
            'name' => $this->name,
            'type' => $this->type,
        ]);

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $this->storageLocation->update([
            'name' => $this->name,
            'type' => $this->type,
        ]);

        $this->reset();
    }
}
