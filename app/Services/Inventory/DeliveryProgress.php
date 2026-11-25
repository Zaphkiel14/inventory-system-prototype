<?php

namespace App\Services\Inventory;

use App\Enums\ReceiptStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for ordered / received / outstanding quantities of a
 * partial delivery. Lines live on the root receipt; received stock is summed
 * from the batches of the root and all of its follow-up deliveries.
 */
class DeliveryProgress
{
    /**
     * @return Collection<int, array{item: InventoryItems, ordered: int, received: int, outstanding: int}>
     */
    public function lines(StockReceipts $root): Collection
    {
        $root = $root->root();

        $receiptIds = StockReceipts::query()
            ->where('id', $root->id)
            ->orWhere('parent_receipt_id', $root->id)
            ->pluck('id');

        $received = InventoryBatches::query()
            ->whereIn('stock_receipt_id', $receiptIds)
            ->selectRaw('inventory_item_id, sum(received_qty) as total')
            ->groupBy('inventory_item_id')
            ->pluck('total', 'inventory_item_id');

        return $root->lines()->with('inventoryItem')->orderBy('id')->get()
            ->map(function ($line) use ($received) {
                $got = (int) ($received[$line->inventory_item_id] ?? 0);

                return [
                    'item' => $line->inventoryItem,
                    'ordered' => (int) $line->ordered_qty,
                    'received' => $got,
                    'outstanding' => max(0, (int) $line->ordered_qty - $got),
                ];
            })
            ->values();
    }

    /**
     * Everything a delivery timeline needs, for the chain of $root.
     *
     * "earlier" is what arrived in deliveries before $current (by id, root first);
     * "now" is what $current itself brought and "later" is what
     * follow-ups after $current brought (0 without $current). Without $current, "earlier" is
     * everything received so far and "now" comes from the $pending quantities
     * per item id (what is being typed into a follow-up form), capped at what
     * is still outstanding.
     *
     * @param  array<int, int>  $pending
     * @return array{
     *     status: ReceiptStatus,
     *     closed_reason: ?string,
     *     ordered: int,
     *     received: int,
     *     outstanding: int,
     *     lines: list<array{item: InventoryItems, ordered: int, earlier: int, now: int, later: int, outstanding: int, closed_short: int}>,
     *     deliveries: list<array{id: int, reference_number: string, status: ReceiptStatus, received_date: CarbonInterface, is_root: bool, is_current: bool, items: list<array{name: string, unit: string, qty: int}>, files: list<array{id: int, name: string, url: ?string, image: bool, pdf: bool, size: ?int}>, inherits_files: bool}>
     * }
     */
    public function timeline(StockReceipts $root, ?StockReceipts $current = null, array $pending = []): array
    {
        $root = $root->root();

        $receipts = StockReceipts::query()
            ->where('id', $root->id)
            ->orWhere('parent_receipt_id', $root->id)
            ->with('attachments')
            ->orderBy('id')
            ->get();

        /** @var array<int, array<int, int>> $received receipt id => item id => qty */
        $received = [];

        InventoryBatches::query()
            ->whereIn('stock_receipt_id', $receipts->modelKeys())
            ->selectRaw('stock_receipt_id, inventory_item_id, sum(received_qty) as total')
            ->groupBy('stock_receipt_id', 'inventory_item_id')
            ->toBase()
            ->get()
            ->each(function ($row) use (&$received) {
                $received[(int) $row->stock_receipt_id][(int) $row->inventory_item_id] = (int) $row->total;
            });

        $rootLines = $root->lines()->with('inventoryItem')->orderBy('id')->get();

        $lines = [];
        $totals = ['ordered' => 0, 'received' => 0, 'outstanding' => 0];

        foreach ($rootLines as $line) {
            $itemId = $line->inventory_item_id;
            $ordered = (int) $line->ordered_qty;
            $earlier = 0;
            $now = 0;
            $later = 0;

            foreach ($receipts as $receipt) {
                $qty = $received[$receipt->id][$itemId] ?? 0;

                if ($current === null || $receipt->id < $current->id) {
                    $earlier += $qty;
                } elseif ($receipt->id === $current->id) {
                    $now = $qty;
                } else {
                    $later += $qty;
                }
            }

            if ($current === null) {
                $now = max(0, min($pending[$itemId] ?? 0, $ordered - $earlier));
            }

            $outstanding = max(0, $ordered - $earlier - $now - $later);

            $lines[] = [
                'item' => $line->inventoryItem,
                'ordered' => $ordered,
                'earlier' => $earlier,
                'now' => $now,
                'later' => $later,
                'outstanding' => $outstanding,
                'closed_short' => $root->status === ReceiptStatus::Closed ? $outstanding : 0,
            ];

            $totals['ordered'] += $ordered;
            $totals['received'] += $earlier + $now + $later;
            $totals['outstanding'] += $outstanding;
        }

        $itemNames = $rootLines->mapWithKeys(fn ($l) => [$l->inventory_item_id => $l->inventoryItem]);
        $missing = collect($received)->flatMap(fn ($items) => array_keys($items))->unique()->diff($itemNames->keys());

        if ($missing->isNotEmpty()) {
            $itemNames = $itemNames->union(InventoryItems::query()->whereIn('id', $missing)->get()->keyBy('id'));
        }

        $deliveries = [];

        foreach ($receipts as $receipt) {
            $files = StockReceipts::attachmentFiles($receipt->attachments);
            $items = [];

            foreach ($received[$receipt->id] ?? [] as $itemId => $qty) {
                $item = $itemNames->get($itemId);
                $items[] = ['name' => (string) $item?->name, 'unit' => (string) $item?->unit, 'qty' => $qty];
            }

            $deliveries[] = [
                'id' => $receipt->id,
                'reference_number' => $receipt->reference_number,
                'status' => $receipt->status,
                'received_date' => $receipt->received_date,
                'is_root' => $receipt->id === $root->id,
                'is_current' => $current !== null && $receipt->id === $current->id,
                'items' => $items,
                'files' => $files,
                'inherits_files' => $receipt->isFollowUp() && $files === [],
            ];
        }

        return [
            'status' => $root->status,
            'closed_reason' => $root->closed_reason,
            ...$totals,
            'lines' => $lines,
            'deliveries' => $deliveries,
        ];
    }

    public function outstanding(StockReceipts $root): int
    {
        return (int) $this->lines($root)->sum('outstanding');
    }

    public function isComplete(StockReceipts $root): bool
    {
        return $root->lines()->exists() && $this->outstanding($root) === 0;
    }

    /** Flip an open root to Fully Delivered once everything ordered has arrived. */
    public function refresh(StockReceipts $root): void
    {
        if ($root->status === ReceiptStatus::Closed || $root->isFollowUp()) {
            return;
        }

        if ($this->isComplete($root)) {
            $root->update(['status' => ReceiptStatus::Complete, 'completed_at' => now()]);
        } elseif ($root->lines()->exists()) {
            $root->update(['status' => ReceiptStatus::Partial, 'completed_at' => null]);
        }
    }

    /** Close an open partial delivery early. Moves no stock. */
    public function close(StockReceipts $root, string $reason): void
    {
        if ($root->isFollowUp() || $root->status !== ReceiptStatus::Partial) {
            throw ValidationException::withMessages([
                'closing' => 'Only an open partial delivery can be closed.',
            ]);
        }

        $root->update([
            'status' => ReceiptStatus::Closed,
            'completed_at' => now(),
            'closed_reason' => $reason,
        ]);
    }
}
