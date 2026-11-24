<?php

namespace App\Livewire\Inventory;

use App\Livewire\Forms\StockReceiptsForm;
use App\Models\InventoryItems;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReceivingCreateForm extends Component
{
    use WithFileUploads;

    public StockReceiptsForm $form;

    /** @var array<int, mixed> */
    public array $attachments = [];

    public function mount(): void
    {
        $this->form->initialize();
    }

    #[Computed]
    public function suppliers()
    {
        return Suppliers::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function inventoryItems()
    {
        return InventoryItems::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    #[Computed]
    public function storageLocations()
    {
        return StorageLocations::query()->orderBy('name')->get();
    }

    public function addItem(): void
    {
        $this->form->addItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->form->items) > 1) {
            $this->form->removeItem($index);
        }
    }

    public function save(): void
    {
        $this->validate(['attachments.*' => ['file', 'max:5120']]);

        $this->form->store($this->attachments);

        $this->reset('attachments');
        $this->form->initialize();

        Flux::modal('create-receiving')->close();

        Flux::toast(
            text: 'Stock received successfully.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('stock-received');
    }

    public function render()
    {
        return view('livewire.inventory.receiving-create-form');
    }
}
