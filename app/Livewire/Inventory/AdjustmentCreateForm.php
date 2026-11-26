<?php

namespace App\Livewire\Inventory;

use App\Enums\AdjustmentType;
use App\Livewire\Forms\InventoryAdjustmentsForm;
use App\Models\InventoryBatches;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Modal form for recording a stock adjustment against one batch.
 *
 * The batch picker is searched on the server: it shows at most BATCH_LIMIT
 * batches (newest first, empty ones included) matching $batchSearch, plus the
 * chosen batch so the select never loses its value. The chosen batch itself is
 * fetched on its own with everything the summary, preview and expiry write-off
 * auto-fill read.
 *
 * @property-read Collection<int, InventoryBatches> $batches
 * @property-read bool $hasMoreBatches
 * @property-read InventoryBatches|null $selectedBatch
 * @property-read array{0: int, 1: int, 2: int}|null $preview
 */
class AdjustmentCreateForm extends Component
{
    /** Most batches the picker lists at once. */
    public const BATCH_LIMIT = 50;

    public InventoryAdjustmentsForm $form;

    /** Batch number, lot number or item name typed into the picker. */
    public string $batchSearch = '';

    /**
     * Per-request memo of the search: up to BATCH_LIMIT + 1 rows, so
     * hasMoreBatches() needs no separate count query.
     *
     * @var Collection<int, InventoryBatches>|null
     */
    protected ?Collection $batchMatches = null;

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
     * Batches for the picker: the first BATCH_LIMIT matches, newest first,
     * with only the columns the options render. The chosen batch is put first
     * when the search or the cap would otherwise leave it out.
     *
     * @return Collection<int, InventoryBatches>
     */
    #[Computed]
    public function batches(): Collection
    {
        $batches = $this->batchMatches()->take(self::BATCH_LIMIT)->values();
        $selected = $this->selectedBatch;

        if ($selected && ! $batches->contains('id', $selected->id)) {
            $batches->prepend($selected);
        }

        return $batches;
    }

    /**
     * Whether the search matched more batches than the picker shows.
     */
    #[Computed]
    public function hasMoreBatches(): bool
    {
        return $this->batchMatches()->count() > self::BATCH_LIMIT;
    }

    /**
     * @return Collection<int, InventoryBatches>
     */
    protected function batchMatches(): Collection
    {
        $term = trim($this->batchSearch);

        return $this->batchMatches ??= InventoryBatches::query()
            ->select(['id', 'inventory_item_id', 'batch_number', 'remaining_qty'])
            ->with('inventoryItem:id,name')
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('batch_number', 'like', "%{$term}%")
                ->orWhere('lot_number', 'like', "%{$term}%")
                ->orWhereHas('inventoryItem', fn (Builder $item) => $item->where('name', 'like', "%{$term}%"))))
            ->latest('received_at')
            ->latest('id')
            ->limit(self::BATCH_LIMIT + 1)
            ->get();
    }

    /**
     * The chosen batch, fetched on its own rather than picked out of the
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
        $this->batchSearch = '';
        $this->batchMatches = null;
        unset($this->batches, $this->hasMoreBatches, $this->selectedBatch, $this->preview);

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
