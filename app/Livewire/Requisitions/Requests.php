<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Models\Requisitions;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Every requisition, with a read-only view modal and cancel for the
 * requester's own pending requests.
 *
 * Deferred: the page paints a skeleton first and loads the list in a request
 * fired straight after paint.
 *
 * @property-read LengthAwarePaginator<int, Requisitions> $requests
 * @property-read Requisitions|null $viewing
 */
#[Defer]
class Requests extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

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

    /**
     * @return LengthAwarePaginator<int, Requisitions>
     */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return Requisitions::query()
            ->with('requester')
            ->withCount('items')
            ->withSum('items', 'requested_qty')
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search, function (Builder $query) {
                $term = "%{$this->search}%";

                $query->where(function (Builder $query) use ($term) {
                    $query->where('request_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('requester_name', 'like', $term)
                        ->orWhere('designation', 'like', $term)
                        ->orWhere('tracking_code', 'like', $term)
                        ->orWhereHas('requester', fn (Builder $q) => $q->where('name', 'like', $term))
                        ->orWhereHas('items.inventoryItem', fn (Builder $q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate($this->perPage);
    }

    /**
     * The requisition in the view modal. Queries only while the modal is open;
     * closeView() clears it when the modal closes.
     */
    #[Computed]
    public function viewing(): ?Requisitions
    {
        return $this->viewingId
            ? Requisitions::query()
                ->with(['requester', 'items.inventoryItem', 'latestApproval.decidedBy'])
                ->find($this->viewingId)
            : null;
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        unset($this->viewing);

        Flux::modal('view-request')->show();
    }

    /**
     * The view modal was closed: stop loading that request on later requests.
     */
    public function closeView(): void
    {
        $this->viewingId = null;
        unset($this->viewing);
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

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.requisitions.placeholders.requests');
    }

    public function render(): View
    {
        return view('livewire.requisitions.requests');
    }
}
