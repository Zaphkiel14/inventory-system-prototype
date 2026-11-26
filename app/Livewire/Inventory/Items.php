<?php

namespace App\Livewire\Inventory;

use App\Models\InventoryItems;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Inventory items list with search, sort and the item details modal.
 *
 * Deferred: the page paints a skeleton first and loads the item list in a request
 * fired straight after paint.
 */
#[Defer]
class Items extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public function sort(string $column): void
    {
        $allowedColumns = [
            'item_code',
            'name',
            'unit',
            'requires_expiry',
            'minimum_stock',
            'reorder_level',
            'is_active',
        ];

        if (! in_array($column, $allowedColumns, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    #[On('inventory-item-created')]
    public function refreshItems(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** Item shown in the read-only details modal. */
    public ?int $viewingId = null;

    public function view(int $itemId): void
    {
        $this->viewingId = $itemId;
        unset($this->viewing);

        Flux::modal('view-item')->show();
    }

    /** Closes the details modal and hands the item to the edit modal. */
    public function editViewing(): void
    {
        if ($this->viewingId === null) {
            return;
        }

        Flux::modal('view-item')->close();
        $this->dispatch('edit-inventory-item', itemId: $this->viewingId);
    }

    #[Computed]
    public function viewing(): ?InventoryItems
    {
        if ($this->viewingId === null) {
            return null;
        }

        return InventoryItems::query()
            ->with(['inventoryCategory' => fn ($q) => $q->orderBy('name')])
            ->withAvailableQty()
            ->withCommittedQty()
            ->withCount([
                'inventoryBatches',
                'inventoryBatches as usable_batches_count' => fn ($q) => $q->usable(),
            ])
            ->find($this->viewingId);
    }

    public function getItemsProperty()
    {
        return InventoryItems::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('item_code', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.inventory.placeholders.items');
    }

    public function render(): View
    {
        return view('livewire.inventory.items');
    }
}
