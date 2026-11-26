<?php

namespace App\Livewire\Inventory;

use App\Enums\AdjustmentType;
use App\Models\InventoryAdjustments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The adjustment log (append-only), with the create form embedded as a modal.
 *
 * Deferred: the page paints a skeleton first and loads the log (and the
 * embedded form) in a request fired straight after paint.
 *
 * @property-read LengthAwarePaginator<int, InventoryAdjustments> $adjustments
 */
#[Defer]
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

    /**
     * @return LengthAwarePaginator<int, InventoryAdjustments>
     */
    #[Computed]
    public function adjustments(): LengthAwarePaginator
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

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.inventory.placeholders.adjustments');
    }

    public function render(): View
    {
        return view('livewire.inventory.adjustments');
    }
}
