<?php

namespace App\Livewire\Inventory;

use App\Enums\ReceiptStatus;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Services\Inventory\DeliveryProgress;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read StockReceipts|null $viewing
 * @property-read LengthAwarePaginator<int, StockReceipts> $receipts
 */
class Receiving extends Component
{
    use WithPagination;

    public string $search = '';

    /** '' = all, 'purchase', 'donation'. */
    public string $type = '';

    public int $perPage = 10;

    /** Receipt shown in the read-only details modal. */
    public ?int $viewingId = null;

    /** Open partial delivery being closed early. */
    public ?int $closingId = null;

    public string $closeReason = '';

    #[On('stock-received')]
    public function refreshReceipts(): void
    {
        $this->resetPage();
        unset($this->receipts, $this->outstandingByReceipt, $this->progressByReceipt, $this->viewing, $this->viewingLines, $this->viewingTimeline, $this->viewingAttachments);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        if (! in_array($this->type, ['', 'purchase', 'donation'], true)) {
            $this->type = '';
        }

        $this->resetPage();
    }

    public function view(int $receiptId): void
    {
        $this->viewingId = $receiptId;
        unset($this->viewing, $this->viewingLines, $this->viewingTimeline, $this->viewingAttachments);

        Flux::modal('view-receipt')->show();
    }

    /** Ask the receiving modal to record a follow-up delivery. Accepts a root or a follow-up id; the root id is dispatched. */
    public function receiveRemaining(int $receiptId): void
    {
        $receipt = StockReceipts::query()->find($receiptId);

        $this->dispatch('receive-follow-up', receiptId: $receipt?->root()->id ?? $receiptId);
    }

    public function confirmClose(int $receiptId): void
    {
        $receipt = StockReceipts::query()->openPartial()->find($receiptId);

        if ($receipt === null) {
            return;
        }

        $this->closingId = $receipt->id;
        $this->closeReason = '';
        $this->resetValidation();

        Flux::modal('close-delivery')->show();
    }

    public function closeDelivery(): void
    {
        $this->validate(['closeReason' => ['required', 'string', 'max:500']], [], ['closeReason' => 'reason']);

        $receipt = StockReceipts::query()->openPartial()->find($this->closingId);

        if ($receipt === null) {
            Flux::modal('close-delivery')->close();
            Flux::toast(text: 'This delivery is already complete or closed.', heading: 'Unable to close', variant: 'danger');

            return;
        }

        app(DeliveryProgress::class)->close($receipt, trim($this->closeReason));

        $this->reset('closingId', 'closeReason');
        unset($this->receipts, $this->outstandingByReceipt, $this->progressByReceipt, $this->viewing, $this->viewingLines, $this->viewingTimeline, $this->viewingAttachments);

        Flux::modal('close-delivery')->close();
        Flux::toast(text: 'Delivery closed.', heading: 'Success', variant: 'success');
    }

    #[Computed]
    public function viewing(): ?StockReceipts
    {
        if ($this->viewingId === null) {
            return null;
        }

        return StockReceipts::query()
            ->with([
                'supplier',
                'receiver',
                'attachments',
                'parent:id,reference_number',
                'followUps' => fn ($q) => $q->orderBy('id'),
                'followUps.attachments',
                'lines.inventoryItem',
                'inventoryBatches' => fn ($q) => $q->orderBy('id'),
                'inventoryBatches.inventoryItem',
                'inventoryBatches.storageLocation',
            ])
            ->find($this->viewingId);
    }

    /**
     * Ordered / received / outstanding per item of the viewed delivery chain;
     * empty when the receipt is not part of a partial delivery.
     *
     * @return Collection<int, array{item: InventoryItems, ordered: int, received: int, outstanding: int}>
     */
    #[Computed]
    public function viewingLines(): Collection
    {
        $viewing = $this->viewing;

        if ($viewing === null) {
            return collect();
        }

        return app(DeliveryProgress::class)->lines($viewing->root());
    }

    /**
     * Timeline of the viewed delivery chain, or null when it has no ordered lines.
     *
     * @return array<string, mixed>|null See DeliveryProgress::timeline() for the shape.
     */
    #[Computed]
    public function viewingTimeline(): ?array
    {
        $viewing = $this->viewing;

        if ($viewing === null || ! $viewing->root()->lines()->exists()) {
            return null;
        }

        return app(DeliveryProgress::class)->timeline($viewing->root(), $viewing);
    }

    /**
     * Files of the WHOLE delivery chain (root + follow-ups), grouped by receipt, oldest first.
     * File shape: id, name, url (null when missing on disk), image, pdf, size (bytes or null).
     *
     * @return list<array{receipt_id: int, reference_number: string, is_current: bool, files: list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>}>
     */
    #[Computed]
    public function viewingAttachments(): array
    {
        $viewing = $this->viewing;

        if ($viewing === null) {
            return [];
        }

        $rootId = $viewing->parent_receipt_id ?? $viewing->id;

        $groups = StockReceipts::query()
            ->where('id', $rootId)
            ->orWhere('parent_receipt_id', $rootId)
            ->with('attachments')
            ->orderBy('id')
            ->get()
            ->map(fn (StockReceipts $r) => [
                'receipt_id' => $r->id,
                'reference_number' => $r->reference_number,
                'is_current' => $r->id === $viewing->id,
                'files' => StockReceipts::attachmentFiles($r->attachments),
            ])
            ->all();

        return array_values($groups);
    }

    #[Computed]
    public function receipts()
    {
        return StockReceipts::query()
            ->with(['supplier', 'receiver', 'parent:id,reference_number,status,parent_receipt_id', 'lines'])
            ->withCount('inventoryBatches')
            ->withSum('inventoryBatches', 'received_qty')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('purchase_order_number', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->type === 'donation', fn ($query) => $query->donations())
            ->when($this->type === 'purchase', fn ($query) => $query->where('is_donation', false))
            ->latest('received_date')
            ->latest('id')
            ->paginate($this->perPage);
    }

    /**
     * Units still outstanding for each open partial delivery on the current page,
     * keyed by receipt id.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function outstandingByReceipt(): array
    {
        $progress = app(DeliveryProgress::class);

        return $this->receipts->getCollection()
            ->filter(fn (StockReceipts $r) => ! $r->isFollowUp() && $r->status === ReceiptStatus::Partial)
            ->mapWithKeys(fn (StockReceipts $r) => [$r->id => $progress->outstanding($r)])
            ->all();
    }

    /**
     * Received / ordered / outstanding units for each open partial delivery on
     * the current page, keyed by receipt id.
     *
     * @return array<int, array{received: int, ordered: int, outstanding: int}>
     */
    #[Computed]
    public function progressByReceipt(): array
    {
        $progress = app(DeliveryProgress::class);
        $out = [];

        foreach ($this->receipts->getCollection() as $receipt) {
            if ($receipt->isFollowUp() || $receipt->status !== ReceiptStatus::Partial) {
                continue;
            }

            $timeline = $progress->timeline($receipt);

            $out[$receipt->id] = [
                'received' => $timeline['received'],
                'ordered' => $timeline['ordered'],
                'outstanding' => $timeline['outstanding'],
            ];
        }

        return $out;
    }

    public function render()
    {
        return view('livewire.inventory.receiving');
    }
}
