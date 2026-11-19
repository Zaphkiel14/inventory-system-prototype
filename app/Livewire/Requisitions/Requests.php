<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Models\Requisitions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Requests extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    /** 'mine' = only the signed-in user's requests, 'all' = everyone's. */
    public string $scope = 'mine';

    public int $perPage = 10;

    public ?int $viewingId = null;

    #[On('request-submitted')]
    public function refreshRequests(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (RequisitionStatus::tryFrom($this->status) === null) {
            $this->status = '';
        }

        $this->resetPage();
    }

    public function updatedScope(): void
    {
        if (! in_array($this->scope, ['mine', 'all'], true)) {
            $this->scope = 'mine';
        }

        $this->resetPage();
    }

    #[Computed]
    public function requests()
    {
        return Requisitions::query()
            ->with('requester')
            ->withCount('items')
            ->withSum('items', 'requested_qty')
            ->when($this->scope === 'mine', fn ($query) => $query->where('user_id', Auth::id()))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('request_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhereHas('requester', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('items.inventoryItem', fn ($q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function viewing(): ?Requisitions
    {
        return $this->viewingId
            ? Requisitions::query()
                ->with(['requester', 'items.inventoryItem'])
                ->find($this->viewingId)
            : null;
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        unset($this->viewing);

        Flux::modal('view-request')->show();
    }

    public function cancel(int $id): void
    {
        $cancelled = DB::transaction(function () use ($id) {
            $requisition = Requisitions::query()->lockForUpdate()->find($id);

            if (! $requisition?->isCancellableBy(Auth::user())) {
                return null;
            }

            $requisition->update([
                'status' => RequisitionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            return $requisition;
        });

        unset($this->requests, $this->viewing);

        if (! $cancelled) {
            Flux::toast(
                text: 'Only your own pending requests can be cancelled.',
                heading: 'Cannot cancel',
                variant: 'danger'
            );

            return;
        }

        Flux::toast(
            text: "Request {$cancelled->request_number} cancelled.",
            heading: 'Cancelled',
            variant: 'success'
        );
    }

    public function render()
    {
        return view('livewire.requisitions.requests');
    }
}
