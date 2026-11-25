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
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read StockReceipts|null $followUpRoot
 */
class ReceivingCreateForm extends Component
{
    use WithFileUploads;

    public StockReceiptsForm $form;

    /** @var array<int, mixed> */
    public array $attachments = [];

    /** Root receipt id when recording a follow-up delivery, otherwise null. */
    public ?int $followUpOf = null;

    public function mount(): void
    {
        $this->form->initialize();
    }

    /** Start a fresh (non-follow-up) receipt. */
    #[On('receive-new')]
    public function startNew(): void
    {
        $this->followUpOf = null;
        $this->form->initialize();
        $this->reset('attachments');
        $this->resetValidation();
        unset($this->followUpRoot, $this->followUpLines, $this->followUpTimeline, $this->inheritedAttachments);
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
        $this->followUpOf = $root->id;
        $this->reset('attachments');
        $this->resetValidation();
        unset($this->followUpRoot, $this->followUpLines, $this->followUpTimeline, $this->inheritedAttachments);

        Flux::modal('create-receiving')->show();
    }

    #[Computed]
    public function suppliers()
    {
        return Suppliers::query()->active()->orderBy('name')->get();
    }

    #[Computed]
    public function inventoryItems()
    {
        return InventoryItems::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    #[Computed]
    public function storageLocations()
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
     * Timeline of the delivery being followed up, including what the form's
     * quantities would bring. Recomputed on every request; null when not a follow-up.
     *
     * @return array<string, mixed>|null See DeliveryProgress::timeline() for the shape.
     */
    #[Computed]
    public function followUpTimeline(): ?array
    {
        $root = $this->followUpRoot;

        if ($root === null) {
            return null;
        }

        $pending = [];

        foreach ($this->form->items as $row) {
            $id = (int) $row['inventory_item_id'];

            if ($id > 0) {
                $pending[$id] = ($pending[$id] ?? 0) + (int) $row['quantity'];
            }
        }

        return app(DeliveryProgress::class)->timeline($root, null, $pending);
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
        unset($this->followUpRoot, $this->followUpLines, $this->followUpTimeline, $this->inheritedAttachments);

        Flux::modal('create-receiving')->close();

        Flux::toast(
            text: 'Stock received successfully.',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('stock-received');
    }

    public function render()
    {
        return view('livewire.inventory.receiving-create-form');
    }
}
