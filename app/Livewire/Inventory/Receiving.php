<?php

namespace App\Livewire\Inventory;

use App\Enums\ReceiptStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Services\Inventory\DeliveryProgress;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @phpstan-type Timeline array{status: ReceiptStatus, closed_reason: ?string, ordered: int, received: int, outstanding: int, lines: list<array{item: InventoryItems, ordered: int, earlier: int, now: int, later: int, outstanding: int, closed_short: int}>, deliveries: list<array{id: int, reference_number: string, status: ReceiptStatus, received_date: CarbonInterface, is_root: bool, is_current: bool, items: list<array{name: string, unit: string, qty: int}>, files: list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>, inherits_files: bool}>}
 *
 * @property-read StockReceipts|null $viewing
 * @property-read LengthAwarePaginator<int, StockReceipts> $receipts
 * @property-read Timeline|null $viewingTimeline
 *
 * Deferred: the page paints a skeleton first and loads the receipt list (and the
 * nested create form) in a request fired straight after paint.
 */
#[Defer]
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
        unset($this->receipts, $this->progressByReceipt, $this->viewing, $this->viewingTimeline, $this->viewingAttachments, $this->viewingSummary);
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
        unset($this->viewing, $this->viewingTimeline, $this->viewingAttachments, $this->viewingSummary);

        Flux::modal('view-receipt')->show();
    }

    /** Ask the receiving modal to record a follow-up delivery. Accepts a root or a follow-up id; the root id is dispatched. */
    public function receiveRemaining(int $receiptId): void
    {
        $receipt = StockReceipts::query()->find($receiptId);

        Flux::modal('view-receipt')->close();

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
        unset($this->receipts, $this->progressByReceipt, $this->viewing, $this->viewingTimeline, $this->viewingAttachments, $this->viewingSummary);

        Flux::modal('close-delivery')->close();
        Flux::toast(text: 'Delivery closed.', heading: 'Success', variant: 'success');
    }

    #[Computed]
    public function viewing(): ?StockReceipts
    {
        if ($this->viewingId === null) {
            return null;
        }

        // Only what the details modal reads. Lines, files and follow-ups come
        // from viewingTimeline() / viewingAttachments(), which query the chain.
        return StockReceipts::query()
            ->with([
                'supplier:id,name',
                'receiver:id,name',
                'parent',
                'inventoryBatches' => fn ($q) => $q->orderBy('id'),
                'inventoryBatches.inventoryItem',
                'inventoryBatches.storageLocation',
            ])
            ->find($this->viewingId);
    }

    /**
     * Timeline of the viewed delivery chain, or null when it has no ordered lines.
     *
     * @return Timeline|null
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
     * Header totals of the details modal. units / batches / total_cost cover the
     * viewed receipt's own batches; ordered / received / outstanding are totals of
     * the whole delivery chain (null when it has no ordered lines).
     *
     * @return array{units: int, batches: int, total_cost: float, is_chain: bool, ordered: ?int, received: ?int, outstanding: ?int, is_closed: bool, can_receive_remaining: bool}|null
     */
    #[Computed]
    public function viewingSummary(): ?array
    {
        $viewing = $this->viewing;

        if ($viewing === null) {
            return null;
        }

        $batches = $viewing->inventoryBatches;
        $timeline = $this->viewingTimeline;

        return [
            'units' => (int) $batches->sum('received_qty'),
            'batches' => $batches->count(),
            'total_cost' => round((float) $batches->sum(fn (InventoryBatches $b) => (int) $b->received_qty * (float) $b->unit_cost), 2),
            'is_chain' => $timeline !== null,
            'ordered' => $timeline['ordered'] ?? null,
            'received' => $timeline['received'] ?? null,
            'outstanding' => $timeline['outstanding'] ?? null,
            'is_closed' => $viewing->root()->status === ReceiptStatus::Closed,
            'can_receive_remaining' => $viewing->isChainOpen(),
        ];
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

        // A chain with ordered lines already has its receipts and files in the
        // timeline (same order, same file shape), so don't load them again.
        if (($timeline = $this->viewingTimeline) !== null) {
            return array_map(fn (array $delivery): array => [
                'receipt_id' => $delivery['id'],
                'reference_number' => $delivery['reference_number'],
                'is_current' => $delivery['is_current'],
                'files' => $delivery['files'],
            ], $timeline['deliveries']);
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

    /** @return LengthAwarePaginator<int, StockReceipts> */
    #[Computed]
    public function receipts(): LengthAwarePaginator
    {
        return StockReceipts::query()
            ->with([
                'supplier:id,name',
                'receiver:id,name',
                'parent:id,reference_number,status,parent_receipt_id',
                'lines:id,stock_receipt_id,inventory_item_id,ordered_qty',
            ])
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
     * Received / ordered / outstanding units for each open partial root receipt
     * on the current page, keyed by receipt id. Ordered comes from the eager-loaded
     * lines; received is one grouped sum over every root and its follow-ups, so the
     * whole page costs a single extra query. Matches DeliveryProgress::timeline() totals.
     *
     * @return array<int, array{received: int, ordered: int, outstanding: int}>
     */
    #[Computed]
    public function progressByReceipt(): array
    {
        $roots = $this->receipts->getCollection()
            ->filter(fn (StockReceipts $r) => ! $r->isFollowUp() && $r->status === ReceiptStatus::Partial);

        if ($roots->isEmpty()) {
            return [];
        }

        $rootIds = $roots->modelKeys();
        $chainRoot = 'coalesce(stock_receipts.parent_receipt_id, stock_receipts.id)';

        /** @var array<int, array<int, int>> $received root id => item id => qty */
        $received = [];

        InventoryBatches::query()
            ->join('stock_receipts', 'stock_receipts.id', '=', 'inventory_batches.stock_receipt_id')
            ->where(function ($query) use ($rootIds) {
                $query->whereIn('stock_receipts.id', $rootIds)
                    ->orWhereIn('stock_receipts.parent_receipt_id', $rootIds);
            })
            ->selectRaw("{$chainRoot} as root_id, inventory_batches.inventory_item_id, sum(inventory_batches.received_qty) as total")
            ->groupByRaw("{$chainRoot}, inventory_batches.inventory_item_id")
            ->toBase()
            ->get()
            ->each(function ($row) use (&$received) {
                $received[(int) $row->root_id][(int) $row->inventory_item_id] = (int) $row->total;
            });

        $out = [];

        foreach ($roots as $root) {
            $totals = ['received' => 0, 'ordered' => 0, 'outstanding' => 0];

            foreach ($root->lines as $line) {
                $ordered = (int) $line->ordered_qty;
                $got = $received[$root->id][$line->inventory_item_id] ?? 0;

                $totals['ordered'] += $ordered;
                $totals['received'] += $got;
                $totals['outstanding'] += max(0, $ordered - $got);
            }

            $out[$root->id] = $totals;
        }

        return $out;
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.inventory.placeholders.receiving');
    }

    public function render(): View
    {
        return view('livewire.inventory.receiving');
    }
}
