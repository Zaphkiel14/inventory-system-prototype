<?php

namespace App\Livewire\Inventory;

use App\Enums\AdjustmentType;
use App\Livewire\Forms\InventoryAdjustmentsForm;
use App\Models\InventoryBatches;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AdjustmentCreateForm extends Component
{
    public InventoryAdjustmentsForm $form;

    public function mount(): void
    {
        $this->form->initialize();
    }

    public function updated(string $property): void
    {
        // Expiry write-offs normally remove everything left in the batch.
        if (in_array($property, ['form.type', 'form.batch_id'], true)
            && $this->form->adjustmentType() === AdjustmentType::ExpiryWriteOff
            && $this->selectedBatch) {
            $this->form->quantity = $this->selectedBatch->remaining_qty;
        }
    }

    #[Computed]
    public function batches()
    {
        return InventoryBatches::query()
            ->with('inventoryItem')
            ->latest('received_at')
            ->latest('id')
            ->get();
    }

    #[Computed]
    public function selectedBatch(): ?InventoryBatches
    {
        return filled($this->form->batch_id)
            ? $this->batches->firstWhere('id', (int) $this->form->batch_id)
            : null;
    }

    /**
     * Live preview of [previous, change, new] shown in the form.
     *
     * @return array{0: int, 1: int, 2: int}|null
     */
    #[Computed]
    public function preview(): ?array
    {
        if (! $this->selectedBatch || ! $this->form->adjustmentType() || $this->form->quantity === '') {
            return null;
        }

        return $this->form->calculate($this->selectedBatch->remaining_qty);
    }

    public function save(): void
    {
        $this->form->store();

        $this->form->initialize();
        unset($this->batches);

        Flux::modal('create-adjustment')->close();

        Flux::toast(
            text: 'Adjustment recorded.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('adjustment-recorded');
    }

    public function render()
    {
        return view('livewire.inventory.adjustment-create-form');
    }
}
