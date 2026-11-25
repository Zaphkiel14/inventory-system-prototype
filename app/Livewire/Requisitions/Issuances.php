<?php

namespace App\Livewire\Requisitions;

use App\Enums\RequisitionStatus;
use App\Livewire\Forms\IssuancesForm;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Services\Inventory\BatchAllocator;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Issuances extends Component
{
    use WithPagination;

    public IssuancesForm $form;

    public string $search = '';

    /** 'releasable' = approved or partially issued (the release queue), '' = all issuance-stage requests. */
    public string $status = 'releasable';

    public int $perPage = 10;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $allowed = ['', 'releasable', ...array_map(fn ($s) => $s->value, $this->stages())];

        if (! in_array($this->status, $allowed, true)) {
            $this->status = 'releasable';
        }

        $this->resetPage();
    }

    /**
     * Statuses that belong to the issuance stage.
     *
     * @return array<int, RequisitionStatus>
     */
    public function stages(): array
    {
        return [RequisitionStatus::Approved, RequisitionStatus::PartiallyIssued, RequisitionStatus::Issued];
    }

    #[Computed]
    public function releasableCount(): int
    {
        return Requisitions::query()->whereIn('status', IssuancesForm::RELEASABLE)->count();
    }

    #[Computed]
    public function requests()
    {
        return Requisitions::query()
            ->with(['requester', 'latestApproval'])
            ->withCount('items')
            ->withSum('items', 'approved_qty')
            ->withSum('items', 'released_qty')
            ->withSum('items', 'outstanding_qty')
            ->whereIn('status', match ($this->status) {
                'releasable' => IssuancesForm::RELEASABLE,
                '' => $this->stages(),
                default => [$this->status],
            })
            ->when($this->search, function ($query) {
                $term = "%{$this->search}%";

                $query->where(function ($query) use ($term) {
                    $query->where('request_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('requester_name', 'like', $term)
                        ->orWhere('designation', 'like', $term)
                        ->orWhere('tracking_code', 'like', $term)
                        ->orWhereHas('requester', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('stockIssuances', fn ($q) => $q
                            ->where('issuance_number', 'like', $term)
                            ->orWhere('received_by', 'like', $term))
                        ->orWhereHas('items.inventoryItem', fn ($q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            ->orderBy('submitted_at', $this->status === 'releasable' ? 'asc' : 'desc')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function releasing(): ?Requisitions
    {
        return $this->form->requisition_id
            ? Requisitions::query()
                ->with([
                    'requester',
                    'latestApproval.decidedBy',
                    'items.inventoryItem',
                    'stockIssuances' => fn ($q) => $q->latest('released_at')->latest('id'),
                    'stockIssuances.stockIssuanceItems.requisitionItem.inventoryItem',
                    'stockIssuances.stockIssuanceItems.batches.inventoryBatch',
                ])
                ->find($this->form->requisition_id)
            : null;
    }

    /**
     * Usable stock on hand per item on this requisition.
     *
     * @return Collection<int, int>
     */
    #[Computed]
    public function onHand(): Collection
    {
        if (! $this->releasing) {
            return collect();
        }

        return InventoryItems::query()
            ->whereKey($this->releasing->items->pluck('inventory_item_id'))
            ->withAvailableQty()
            ->pluck('available_qty', 'id')
            ->map(fn ($qty) => (int) $qty);
    }

    /**
     * Which batches each line would be drawn from for the quantities entered.
     *
     * @return array<int, array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}>
     */
    #[Computed]
    public function preview(): array
    {
        if (! $this->releasing) {
            return [];
        }

        $allocator = app(BatchAllocator::class);
        $preview = [];

        foreach ($this->releasing->items as $line) {
            $qty = (int) ($this->form->release[$line->id] ?? 0);

            if ($qty > 0 && $line->inventoryItem) {
                $preview[$line->id] = $allocator->allocate($line->inventoryItem, $qty);
            }
        }

        return $preview;
    }

    public function open(int $id): void
    {
        $requisition = Requisitions::query()->with(['requester', 'items'])->findOrFail($id);

        $this->form->load($requisition);
        $this->forgetReleasing();

        Flux::modal('release-request')->show();
    }

    /**
     * Checks the entered fields, then asks for confirmation before release().
     */
    public function confirmRelease(): void
    {
        $this->form->validateInput();

        Flux::modal('confirm-release')->show();
    }

    public function release(): void
    {
        Flux::modal('confirm-release')->close();

        $issuance = $this->form->store();

        $this->form->initialize();
        $this->forgetReleasing();
        unset($this->requests, $this->releasableCount);

        Flux::modal('release-request')->close();

        Flux::toast(
            text: "Issuance {$issuance->issuance_number} recorded.",
            heading: 'Items released',
            variant: 'success'
        );
    }

    protected function forgetReleasing(): void
    {
        unset($this->releasing, $this->onHand, $this->preview);
    }

    public function render()
    {
        return view('livewire.requisitions.issuances');
    }
}
