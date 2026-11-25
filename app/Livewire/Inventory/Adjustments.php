<?php

namespace App\Livewire\Inventory;

use App\Enums\AdjustmentType;
use App\Models\InventoryAdjustments;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Adjustments extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public int $perPage = 15;

    #[On('adjustment-recorded')]
    public function refreshAdjustments(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        if (AdjustmentType::tryFrom($this->type) === null) {
            $this->type = '';
        }

        $this->resetPage();
    }

    #[Computed]
    public function adjustments()
    {
        return InventoryAdjustments::query()
            ->with(['inventoryBatch.inventoryItem', 'inventoryBatch.storageLocation', 'recordedBy'])
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('reason', 'like', $term)
                        ->orWhere('requested_by', 'like', $term)
                        ->orWhere('approved_by', 'like', $term)
                        ->orWhereHas('inventoryBatch', fn ($q) => $q
                            ->where('batch_number', 'like', $term)
                            ->orWhere('lot_number', 'like', $term)
                            ->orWhereHas('inventoryItem', fn ($q) => $q
                                ->where('name', 'like', $term)
                                ->orWhere('item_code', 'like', $term)));
                });
            })
            ->latest()
            ->latest('id')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.inventory.adjustments');
    }
}
