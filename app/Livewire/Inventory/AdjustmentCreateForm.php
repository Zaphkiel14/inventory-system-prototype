<?php

namespace App\Livewire\Inventory;

use App\Enums\AdjustmentType;
use App\Livewire\Forms\InventoryAdjustmentsForm;
use App\Models\InventoryBatches;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Modal form for recording a stock adjustment against one batch.
 *
 * The batch dropdown lists every batch (including empty ones) but loads only
 * the columns its options show; the chosen batch is fetched on its own with
 * everything the summary, preview and expiry write-off auto-fill read.
 *
 * @property-read Collection<int, InventoryBatches> $batches
 * @property-read InventoryBatches|null $selectedBatch
 * @property-read array{0: int, 1: int, 2: int}|null $preview
 */
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

    /**
     * Every batch for the dropdown, newest first, with only the columns the
     * options render (batch number, item name, remaining quantity).
     *
     * @return Collection<int, InventoryBatches>
     */
    #[Computed]
    public function batches(): Collection
    {
        return InventoryBatches::query()
            ->select(['id', 'inventory_item_id', 'batch_number', 'remaining_qty'])
            ->with('inventoryItem:id,name')
            ->latest('received_at')
            ->latest('id')
            ->get();
    }

    /**
     * The chosen batch, fetched on its own rather than picked out of the full
     * dropdown list.
     */
    #[Computed]
    public function selectedBatch(): ?InventoryBatches
    {
        return filled($this->form->batch_id)
            ? InventoryBatches::query()->with('inventoryItem')->find((int) $this->form->batch_id)
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
        unset($this->batches, $this->selectedBatch, $this->preview);

        Flux::modal('create-adjustment')->close();

        Flux::toast(
            text: 'Adjustment recorded.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('adjustment-recorded');
    }

    public function render(): View
    {
        return view('livewire.inventory.adjustment-create-form');
    }
}
