<?php

namespace App\Livewire\Configurations;

use App\Models\StorageLocations as StorageLocation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Storage locations list with search, sort and delete.
 *
 * Deferred: the page paints a skeleton first and loads the location list in a request
 * fired straight after paint.
 */
#[Defer]
class StorageLocations extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'type', 'inventory_batches_count'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[On('storage-location-saved')]
    public function refreshLocations(): void
    {
        unset($this->locations);
    }

    public function delete(int $id): void
    {
        $location = StorageLocation::withCount('inventoryBatches')->findOrFail($id);

        if ($location->inventory_batches_count > 0) {
            Flux::toast(
                text: "{$location->name} still has {$location->inventory_batches_count} batch(es). Move or adjust them first.",
                heading: 'Cannot delete',
                variant: 'danger'
            );

            return;
        }

        $location->delete();

        unset($this->locations);

        Flux::toast(
            text: 'Storage location deleted successfully',
            heading: 'Success',
            variant: 'success'
        );
    }

    #[Computed]
    public function locations()
    {
        return StorageLocation::query()
            ->withCount('inventoryBatches')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('type', 'like', "%{$this->search}%");
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
        return view('livewire.configurations.placeholders.storage-locations');
    }

    public function render(): View
    {
        return view('livewire.configurations.storage-locations');
    }
}
