<?php

namespace App\Livewire\Inventory;

use App\Models\Suppliers as Supplier;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supplier list with search, status filter, sorting, activate/deactivate and
 * delete (only for suppliers without deliveries).
 *
 * Deferred: the page paints a skeleton first and loads the suppliers in a
 * request fired straight after paint.
 */
#[Defer]
class Suppliers extends Component
{
    use WithPagination;

    public string $search = '';

    /** all | active | inactive */
    #[Url(except: 'all')]
    public string $status = 'all';

    public string $sortBy = 'name';

    /** @var 'asc'|'desc' */
    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'contact_person', 'is_active', 'stock_receipts_count', 'stock_receipts_max_received_date'], true)) {
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

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[On('supplier-saved')]
    public function refreshSuppliers(): void
    {
        unset($this->suppliers);
    }

    public function toggleActive(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => ! $supplier->is_active]);

        unset($this->suppliers);

        Flux::toast(
            text: $supplier->is_active
                ? "{$supplier->name} is active again and can be picked when receiving stock."
                : "{$supplier->name} is deactivated and won't appear when receiving stock.",
            heading: 'Success',
            variant: 'success'
        );
    }

    public function delete(int $id): void
    {
        $supplier = Supplier::withCount('stockReceipts')->findOrFail($id);

        if ($supplier->stock_receipts_count > 0) {
            Flux::toast(
                text: "{$supplier->name} has {$supplier->stock_receipts_count} delivery record(s). Deactivate it instead.",
                heading: 'Cannot delete',
                variant: 'danger'
            );

            return;
        }

        $supplier->delete();

        unset($this->suppliers);

        Flux::toast(
            text: 'Supplier deleted successfully',
            heading: 'Success',
            variant: 'success'
        );
    }

    /**
     * @return LengthAwarePaginator<int, Supplier>
     */
    #[Computed]
    public function suppliers(): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('stockReceipts')
            ->withMax('stockReceipts', 'received_date')
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('contact_person', 'like', "%{$this->search}%")
                        ->orWhere('contact', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.inventory.placeholders.suppliers');
    }

    public function render(): View
    {
        return view('livewire.inventory.suppliers');
    }
}
