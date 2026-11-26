<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Livewire\Forms\ApprovalsForm;
use App\Models\Requisitions;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The approval queue and the review modal. Approving only commits quantities
 * (outstanding_qty); stock moves at issuance.
 *
 * Deferred: the page paints a skeleton first and loads the queue in a request
 * fired straight after paint.
 *
 * @property-read LengthAwarePaginator<int, Requisitions> $requests
 * @property-read int $pendingCount
 * @property-read Requisitions|null $reviewing
 * @property-read Collection<int, array{on_hand: int, committed: int, free: int}> $stock
 */
#[Defer]
class Approvals extends Component
{
    use WithPagination;

    public ApprovalsForm $form;

    public string $search = '';

    /** Defaults to the approval queue; '' shows every status. */
    public string $status = 'pending';

    public int $perPage = 10;

    /**
     * The requisition review() already loaded with everything the review modal
     * shows, so reviewing() does not query it again in the same request.
     */
    protected ?Requisitions $loadedReviewing = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if ($this->status !== '' && RequisitionStatus::tryFrom($this->status) === null) {
            $this->status = 'pending';
        }

        $this->resetPage();
    }

    /**
     * Requests waiting for a decision. On the unfiltered pending queue the
     * list's own total is exactly this number, so that count is reused.
     */
    #[Computed]
    public function pendingCount(): int
    {
        if ($this->status === RequisitionStatus::Pending->value && $this->search === '') {
            return $this->requests->total();
        }

        return Requisitions::query()->where('status', RequisitionStatus::Pending)->count();
    }

    /**
     * @return LengthAwarePaginator<int, Requisitions>
     */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return Requisitions::query()
            ->with(['requester', 'latestApproval.decidedBy'])
            ->withCount('items')
            ->withSum('items', 'requested_qty')
            ->withSum('items', 'approved_qty')
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
            // The queue is worked oldest-first; history reads newest-first.
            ->orderBy('submitted_at', $this->status === RequisitionStatus::Pending->value ? 'asc' : 'desc')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /**
     * The requisition in the review modal. Queries only while the modal is
     * open (form loaded); dismissReview() clears it when the modal closes.
     */
    #[Computed]
    public function reviewing(): ?Requisitions
    {
        if (! $this->form->requisition_id) {
            return null;
        }

        if ($this->loadedReviewing?->id === $this->form->requisition_id) {
            return $this->loadedReviewing;
        }

        return $this->reviewingQuery()->find($this->form->requisition_id);
    }

    /**
     * @return Builder<Requisitions>
     */
    protected function reviewingQuery(): Builder
    {
        return Requisitions::query()->with(['requester', 'items.inventoryItem', 'latestApproval.decidedBy']);
    }

    /**
     * @return Collection<int, array{on_hand: int, committed: int, free: int}>
     */
    #[Computed]
    public function stock(): Collection
    {
        return $this->reviewing
            ? ApprovalsForm::stockFor($this->reviewing->items->pluck('inventory_item_id'))
            : collect();
    }

    public function review(int $id): void
    {
        $requisition = $this->reviewingQuery()->findOrFail($id);

        $this->form->load($requisition);
        $this->loadedReviewing = $requisition;
        unset($this->reviewing, $this->stock);

        Flux::modal('review-request')->show();
    }

    /**
     * Quick-fill every line with the most that can be approved.
     */
    public function fillMaximum(): void
    {
        if ($this->reviewing) {
            foreach ($this->reviewing->items as $line) {
                $this->form->approved[$line->id] = min(
                    $line->requested_qty,
                    $this->stock[$line->inventory_item_id]['free'] ?? 0,
                );
            }
        }
    }

    public function approve(): void
    {
        $requisition = $this->form->approve();

        $this->closeReview();

        Flux::toast(
            text: "Request {$requisition->request_number} approved and queued for issuance.",
            heading: 'Approved',
            variant: 'success'
        );
    }

    public function reject(): void
    {
        $requisition = $this->form->reject();

        $this->closeReview();

        Flux::toast(
            text: "Request {$requisition->request_number} rejected.",
            heading: 'Rejected',
            variant: 'success'
        );
    }

    /**
     * The review modal was closed without a decision: drop the loaded request
     * so its stock picture stops querying on later requests.
     */
    public function dismissReview(): void
    {
        $this->form->initialize();
        $this->loadedReviewing = null;
        unset($this->reviewing, $this->stock);
    }

    protected function closeReview(): void
    {
        $this->form->initialize();
        $this->loadedReviewing = null;
        unset($this->reviewing, $this->stock, $this->requests, $this->pendingCount);

        Flux::modal('review-request')->close();
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.requisitions.placeholders.approvals');
    }

    public function render(): View
    {
        return view('livewire.requisitions.approvals');
    }
}
