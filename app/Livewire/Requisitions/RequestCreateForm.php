<?php

namespace App\Livewire\Requisitions;

use App\Livewire\Forms\RequisitionsForm;
use App\Models\InventoryItems;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RequestCreateForm extends Component
{
    public RequisitionsForm $form;

    public function mount(): void
    {
        $this->form->initialize();
    }

    #[Computed]
    public function inventoryItems()
    {
        return InventoryItems::query()
            ->where('is_active', true)
            ->withAvailableQty()
            ->orderBy('name')
            ->get()
            ->keyBy('id');
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
        $requisition = $this->form->store();

        $this->form->initialize();
        unset($this->inventoryItems);

        Flux::modal('create-request')->close();

        Flux::toast(
            text: "Request {$requisition->request_number} submitted for approval.",
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('request-submitted');
    }

    public function render()
    {
        return view('livewire.requisitions.request-create-form');
    }
}
