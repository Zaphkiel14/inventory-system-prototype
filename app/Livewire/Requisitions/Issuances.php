<?php

namespace App\Livewire\Requisitions;

use App\Enums\PropertyDocumentType;
use App\Enums\RequisitionStatus;
use App\Livewire\Forms\IssuancesForm;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Services\Inventory\BatchAllocator;
use App\Services\Property\PropertyDocumentDrafter;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The release queue: approved and partially issued requisitions, and the
 * release modal that previews which batches each line would draw from.
 *
 * Deferred: the page paints a skeleton first and loads the queue in a request
 * fired straight after paint.
 *
 * @property-read LengthAwarePaginator<int, Requisitions> $requests
 * @property-read int $releasableCount
 * @property-read Requisitions|null $releasing
 * @property-read Collection<int, int> $onHand
 * @property-read array<int, array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}> $preview
 */
#[Defer]
class Issuances extends Component
{
    use WithPagination;

    public IssuancesForm $form;

    public string $search = '';

    /** 'releasable' = approved or partially issued (the release queue), '' = all issuance-stage requests. */
    public string $status = 'releasable';

    public int $perPage = 10;

    /**
     * The requisition open() already loaded with everything the release modal
     * shows, so releasing() does not query it again in the same request.
     */
    protected ?Requisitions $loadedReleasing = null;

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

    /**
     * Requests waiting for release. On the unfiltered release queue the list's
     * own total is exactly this number, so that count is reused.
     */
    #[Computed]
    public function releasableCount(): int
    {
        if ($this->status === 'releasable' && $this->search === '') {
            return $this->requests->total();
        }

        return Requisitions::query()->whereIn('status', IssuancesForm::RELEASABLE)->count();
    }

    /**
     * @return LengthAwarePaginator<int, Requisitions>
     */
    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return Requisitions::query()
            ->with('latestApproval')
            ->withCount('items')
            ->withSum('items', 'approved_qty')
            ->withSum('items', 'released_qty')
            ->withSum('items', 'outstanding_qty')
            ->whereIn('status', match ($this->status) {
                'releasable' => IssuancesForm::RELEASABLE,
                '' => $this->stages(),
                default => [$this->status],
            })
            ->when($this->search, function (Builder $query) {
                $term = "%{$this->search}%";

                $query->where(function (Builder $query) use ($term) {
                    $query->where('request_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhere('requester_name', 'like', $term)
                        ->orWhere('designation', 'like', $term)
                        ->orWhere('tracking_code', 'like', $term)
                        ->orWhereHas('requester', fn (Builder $q) => $q->where('name', 'like', $term))
                        ->orWhereHas('stockIssuances', fn (Builder $q) => $q
                            ->where('issuance_number', 'like', $term)
                            ->orWhere('received_by', 'like', $term))
                        ->orWhereHas('items.inventoryItem', fn (Builder $q) => $q
                            ->where('name', 'like', $term)
                            ->orWhere('item_code', 'like', $term));
                });
            })
            ->orderBy('submitted_at', $this->status === 'releasable' ? 'asc' : 'desc')
            ->orderBy('id')
            ->paginate($this->perPage);
    }

    /**
     * The requisition in the release modal. Queries only while the modal is
     * open (form loaded); dismissRelease() clears it when the modal closes.
     */
    #[Computed]
    public function releasing(): ?Requisitions
    {
        if (! $this->form->requisition_id) {
            return null;
        }

        if ($this->loadedReleasing?->id === $this->form->requisition_id) {
            return $this->loadedReleasing;
        }

        return $this->releasingQuery()->find($this->form->requisition_id);
    }

    /**
     * @return Builder<Requisitions>
     */
    protected function releasingQuery(): Builder
    {
        return Requisitions::query()->with([
            'latestApproval.decidedBy',
            'items.inventoryItem',
            'stockIssuances' => fn ($q) => $q->latest('released_at')->latest('id'),
            'stockIssuances.stockIssuanceItems.requisitionItem.inventoryItem',
            'stockIssuances.stockIssuanceItems.batches.inventoryBatch',
        ]);
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

        $lines = $this->releasing->items
            ->filter(fn ($line) => (int) ($this->form->release[$line->id] ?? 0) > 0 && $line->inventoryItem)
            ->sortBy('id');

        if ($lines->isEmpty()) {
            return [];
        }

        // Every line's batch queue in at most two queries, then planned in PHP
        // cumulatively in line order, exactly as IssuancesForm::store() draws.
        $allocator = app(BatchAllocator::class);
        $queues = $allocator->queueFor($lines->pluck('inventoryItem')->unique('id')->values());
        $taken = [];
        $preview = [];

        foreach ($lines as $line) {
            $preview[$line->id] = $allocator->allocateFrom(
                $queues[$line->inventory_item_id],
                (int) $this->form->release[$line->id],
                $taken,
            );
        }

        return $preview;
    }

    public function open(int $id): void
    {
        $requisition = $this->releasingQuery()->findOrFail($id);

        $this->form->load($requisition);
        $this->forgetReleasing();
        $this->loadedReleasing = $requisition;
        $this->planUnits();

        Flux::modal('release-request')->show();
    }

    /**
     * A changed release quantity re-plans the batches and the property unit rows.
     */
    public function updatedForm(mixed $value, string $key): void
    {
        if (str_starts_with($key, 'release')) {
            unset($this->preview);
            $this->planUnits();
        }
    }

    /**
     * Rebuild the per-unit rows (brand/model/serial) from the batch preview.
     */
    public function planUnits(): void
    {
        if ($this->releasing) {
            $this->form->planUnits($this->releasing, $this->preview);
        }
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

        try {
            $issuance = $this->form->store();
        } catch (ValidationException $e) {
            // Stock may have moved since the preview: show the current plan.
            unset($this->preview);
            $this->planUnits();

            throw $e;
        }

        $this->dispatch('stock-issued',
            issuanceId: $issuance->id,
            pendingPropertyUnits: $issuance->pendingPropertyUnitsCount(),
        );

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

    /**
     * Draft the ICS/PAR documents for an issuance's property units, then go to
     * the ICS list (or the PAR list when there are no ICS drafts) filtered to
     * that issuance. The counts are flashed as "property-documents-drafted".
     */
    public function draftPropertyDocuments(int $issuanceId): void
    {
        $drafts = app(PropertyDocumentDrafter::class)->forIssuance(StockIssuances::query()->findOrFail($issuanceId));

        $ics = $drafts->where('type', PropertyDocumentType::Ics)->count();
        $par = $drafts->where('type', PropertyDocumentType::Par)->count();

        session()->flash('property-documents-drafted', $drafts->isEmpty()
            ? 'This issuance has no property units waiting for an ICS or PAR.'
            : sprintf('%d ICS and %d PAR draft%s ready to complete.', $ics, $par, $ics + $par === 1 ? '' : 's'));

        $this->redirectRoute($ics > 0 || $par === 0 ? 'property-management.ics' : 'property-management.par', ['issuance' => $issuanceId]);
    }

    /**
     * The release modal was closed without releasing: drop the loaded request
     * so its stock and batch preview stop querying on later requests.
     */
    public function dismissRelease(): void
    {
        $this->form->initialize();
        $this->forgetReleasing();
    }

    protected function forgetReleasing(): void
    {
        $this->loadedReleasing = null;
        unset($this->releasing, $this->onHand, $this->preview);
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.requisitions.placeholders.issuances');
    }

    public function render(): View
    {
        return view('livewire.requisitions.issuances');
    }
}
