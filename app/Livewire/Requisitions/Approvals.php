<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Livewire\Forms\ApprovalsForm;
use App\Models\Requisitions;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Approvals extends Component
{
    use WithPagination;

    public ApprovalsForm $form;

    public string $search = '';

    /** Defaults to the approval queue; '' shows every status. */
    public string $status = 'pending';

    public int $perPage = 10;

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

    #[Computed]
    public function pendingCount(): int
    {
        return Requisitions::query()->where('status', RequisitionStatus::Pending)->count();
    }

    #[Computed]
    public function requests()
    {
        return Requisitions::query()
            ->with(['requester', 'latestApproval.decidedBy'])
            ->withCount('items')
            ->withSum('items', 'requested_qty')
            ->withSum('items', 'approved_qty')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('request_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('requester_name', 'like', $term)
                        ->orWhere('designation', 'like', $term)
                        ->orWhere('tracking_code', 'like', $term)
                        ->orWhereHas('requester', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('items.inventoryItem', fn ($q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            // The queue is worked oldest-first; history reads newest-first.
            ->orderBy('submitted_at', $this->status === RequisitionStatus::Pending->value ? 'asc' : 'desc')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function reviewing(): ?Requisitions
    {
        return $this->form->requisition_id
            ? Requisitions::query()
                ->with(['requester', 'items.inventoryItem', 'latestApproval.decidedBy'])
                ->find($this->form->requisition_id)
            : null;
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
        $requisition = Requisitions::query()->with('items')->findOrFail($id);

        $this->form->load($requisition);
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

    protected function closeReview(): void
    {
        $this->form->initialize();
        unset($this->reviewing, $this->stock, $this->requests, $this->pendingCount);

        Flux::modal('review-request')->close();
    }

    public function render()
    {
        return view('livewire.requisitions.approvals');
    }
}
