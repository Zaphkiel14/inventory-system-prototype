<?php

namespace App\Livewire\Inventory;

use App\Enums\ReceiptStatus;
use App\Livewire\Forms\StockReceiptsForm;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Services\Inventory\DeliveryProgress;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read StockReceipts|null $followUpRoot
 * @property-read Collection<int, array{item: InventoryItems, ordered: int, received: int, outstanding: int}> $followUpLines
 * @property-read EloquentCollection<int, InventoryItems> $inventoryItems
 */
class ReceivingCreateForm extends Component
{
    use WithFileUploads;

    public StockReceiptsForm $form;

    /** @var array<int, mixed> */
    public array $attachments = [];

    /** Root receipt id when recording a follow-up delivery, otherwise null. */
    public ?int $followUpOf = null;

    /**
     * UI mirror of form.is_donation for the Purchase / Donation toggle: 'purchase'
     * or 'donation'. A string because Flux radio groups match options by string
     * value; form.is_donation stays the source of truth that is stored.
     */
    public string $receiptType = 'purchase';

    public function mount(): void
    {
        $this->form->initialize();
        $this->syncReceiptType();
    }

    public function updatedReceiptType(): void
    {
        if (! in_array($this->receiptType, ['purchase', 'donation'], true)) {
            $this->receiptType = 'purchase';
        }

        $this->form->is_donation = $this->receiptType === 'donation';
    }

    /** Start a fresh (non-follow-up) receipt. */
    #[On('receive-new')]
    public function startNew(): void
    {
        $this->followUpOf = null;
        $this->form->initialize();
        $this->syncReceiptType();
        $this->reset('attachments');
        $this->resetValidation();
        unset($this->followUpRoot, $this->followUpLines, $this->followUpRows, $this->followUpTimeline, $this->inheritedAttachments);
    }

    /** Open the modal prefilled with what is still outstanding on an open partial delivery. */
    #[On('receive-follow-up')]
    public function followUp(int $receiptId): void
    {
        $root = StockReceipts::query()->find($receiptId)?->root();

        if ($root === null || $root->status !== ReceiptStatus::Partial) {
            Flux::toast(
                text: 'This delivery is already complete or closed.',
                heading: 'Unable to continue',
                variant: 'danger'
            );

            return;
        }

        $this->form->loadFollowUp($root);
        $this->syncReceiptType();
        $this->followUpOf = $root->id;
        $this->reset('attachments');
        $this->resetValidation();
        unset($this->followUpRoot, $this->followUpLines, $this->followUpRows, $this->followUpTimeline, $this->inheritedAttachments);

        Flux::modal('create-receiving')->show();
    }

    /** @return EloquentCollection<int, Suppliers> */
    #[Computed]
    public function suppliers(): EloquentCollection
    {
        return Suppliers::query()->active()->orderBy('name')->get();
    }

    /** @return EloquentCollection<int, InventoryItems> */
    #[Computed]
    public function inventoryItems(): EloquentCollection
    {
        return InventoryItems::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /** @return EloquentCollection<int, StorageLocations> */
    #[Computed]
    public function storageLocations(): EloquentCollection
    {
        return StorageLocations::query()->orderBy('name')->get();
    }

    #[Computed]
    public function followUpRoot(): ?StockReceipts
    {
        if ($this->followUpOf === null) {
            return null;
        }

        return StockReceipts::query()->with(['supplier', 'lines.inventoryItem'])->find($this->followUpOf);
    }

    /**
     * Ordered / received / outstanding per item of the delivery being followed up.
     *
     * @return Collection<int, array{item: InventoryItems, ordered: int, received: int, outstanding: int}>
     */
    #[Computed]
    public function followUpLines(): Collection
    {
        $root = $this->followUpRoot;

        return $root === null ? collect() : app(DeliveryProgress::class)->lines($root);
    }

    /**
     * One entry per form row of a follow-up delivery (index = form row index),
     * matched to the outstanding line of the same item. receiving_now is the row's
     * own quantity; remaining_after is per ITEM: outstanding minus what every form
     * row of that item brings, so all rows of one item show the same value.
     * Empty when not a follow-up.
     *
     * @return list<array{index: int, item: ?InventoryItems, ordered: int, received: int, outstanding: int, receiving_now: int, remaining_after: int}>
     */
    #[Computed]
    public function followUpRows(): array
    {
        $lines = $this->followUpLines->keyBy(fn (array $line) => $line['item']->id);

        if ($lines->isEmpty()) {
            return [];
        }

        /** @var array<int, int> $receivingByItem item id => quantity across all form rows */
        $receivingByItem = [];

        foreach ($this->form->items as $row) {
            $itemId = (int) $row['inventory_item_id'];
            $receivingByItem[$itemId] = ($receivingByItem[$itemId] ?? 0) + (int) $row['quantity'];
        }

        $rows = [];

        foreach ($this->form->items as $index => $row) {
            $itemId = (int) $row['inventory_item_id'];
            $line = $lines->get($itemId);
            $outstanding = $line['outstanding'] ?? 0;

            $rows[] = [
                'index' => (int) $index,
                'item' => $line['item'] ?? $this->inventoryItems->get($itemId),
                'ordered' => $line['ordered'] ?? 0,
                'received' => $line['received'] ?? 0,
                'outstanding' => $outstanding,
                'receiving_now' => (int) $row['quantity'],
                'remaining_after' => max(0, $outstanding - $receivingByItem[$itemId]),
            ];
        }

        return $rows;
    }

    /**
     * Timeline of the delivery being followed up (the view reads its 'deliveries'
     * for the "Previous deliveries" list); null when not a follow-up.
     *
     * @return array<string, mixed>|null See DeliveryProgress::timeline() for the shape.
     */
    #[Computed]
    public function followUpTimeline(): ?array
    {
        $root = $this->followUpRoot;

        return $root === null ? null : app(DeliveryProgress::class)->timeline($root);
    }

    /**
     * Files already attached to the root and its follow-ups (reused by a follow-up).
     *
     * @return list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>
     */
    #[Computed]
    public function inheritedAttachments(): array
    {
        if ($this->followUpOf === null) {
            return [];
        }

        $attachments = StockReceipts::query()
            ->where('id', $this->followUpOf)
            ->orWhere('parent_receipt_id', $this->followUpOf)
            ->with('attachments')
            ->orderBy('id')
            ->get()
            ->flatMap->attachments;

        return StockReceipts::attachmentFiles($attachments);
    }

    public function addItem(): void
    {
        $this->form->addItem();
    }

    public function removeItem(int $index): void
    {
        if (count($this->form->items) > 1) {
            $this->form->removeItem($index);
        }
    }

    public function save(): void
    {
        // Field errors first, so a missing attachment is the last thing reported.
        $this->form->validateDelivery();

        $file = ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'];

        $this->validate(
            $this->form->parent_receipt_id === null
                ? ['attachments' => ['required', 'array', 'min:1'], 'attachments.*' => $file]
                : ['attachments' => ['nullable', 'array'], 'attachments.*' => $file],
            ['attachments.required' => 'Attach at least one file (delivery receipt, invoice or deed of donation).',
                'attachments.min' => 'Attach at least one file (delivery receipt, invoice or deed of donation).'],
        );

        $this->form->store($this->attachments);

        $this->reset('attachments');
        $this->followUpOf = null;
        $this->form->initialize();
        $this->syncReceiptType();
        unset($this->followUpRoot, $this->followUpLines, $this->followUpRows, $this->followUpTimeline, $this->inheritedAttachments);

        Flux::modal('create-receiving')->close();

        Flux::toast(
            text: 'Stock received successfully.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('stock-received');
    }

    /** Point the toggle at whatever the form currently holds. */
    private function syncReceiptType(): void
    {
        $this->receiptType = $this->form->is_donation ? 'donation' : 'purchase';
    }

    public function render(): View
    {
        return view('livewire.inventory.receiving-create-form');
    }
}
