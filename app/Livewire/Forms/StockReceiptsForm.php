<?php

namespace App\Livewire\Forms;

use App\Enums\InventoryTransactionType;
use App\Enums\ReceiptStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceiptLines;
use App\Models\StockReceipts;
use App\Services\Inventory\BatchCodeGenerator;
use App\Services\Inventory\DeliveryProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class StockReceiptsForm extends Form
{
    public $supplier_id = '';

    public string $purchase_order_number = '';

    public string $reference_number = '';

    public ?string $received_date = null;

    public ?string $delivery_date = null;

    public string $status = 'complete_delivery';

    public string $remarks = '';

    public bool $is_donation = false;

    /** Set when this delivery follows up an open partial delivery (the root receipt). */
    public ?int $parent_receipt_id = null;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function initialize(): void
    {
        $this->reset();
        $this->received_date = now()->format('Y-m-d\TH:i');
        $this->delivery_date = $this->received_date;
        $this->addItem();
    }

    /**
     * Prefill a follow-up delivery for an open partial root: supplier, PO and
     * donation tag come from the root, lines from what is still outstanding.
     */
    public function loadFollowUp(StockReceipts $root): void
    {
        $this->reset();

        $root = $root->root();

        $this->parent_receipt_id = $root->id;
        $this->supplier_id = $root->supplier_id;
        $this->purchase_order_number = (string) $root->purchase_order_number;
        $this->is_donation = (bool) $root->is_donation;
        $this->received_date = now()->format('Y-m-d\TH:i');
        $this->delivery_date = $this->received_date;

        $receiptIds = StockReceipts::query()
            ->where('id', $root->id)
            ->orWhere('parent_receipt_id', $root->id)
            ->pluck('id');

        foreach (app(DeliveryProgress::class)->lines($root) as $line) {
            if ($line['outstanding'] < 1) {
                continue;
            }

            $this->addItem();
            $index = count($this->items) - 1;

            $this->items[$index]['inventory_item_id'] = $line['item']->id;
            $this->items[$index]['quantity'] = $line['outstanding'];
            $this->items[$index]['storage_location_id'] = InventoryBatches::query()
                ->whereIn('stock_receipt_id', $receiptIds)
                ->where('inventory_item_id', $line['item']->id)
                ->latest('id')
                ->value('storage_location_id') ?? '';
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'inventory_item_id' => '',
            'storage_location_id' => '',
            'batch_number' => '',
            'lot_number' => '',
            'ordered_qty' => '',
            'quantity' => '',
            'unit_cost' => '',
            'manufactured_date' => null,
            'expiration_date' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        $requiresExpiry = InventoryItems::query()
            ->whereIn('id', collect($this->items)->pluck('inventory_item_id')->filter())
            ->where('requires_expiry', true)
            ->pluck('id')
            ->all();

        $followUp = $this->parent_receipt_id !== null;
        $partialNew = ! $followUp && $this->status === ReceiptStatus::Partial->value;
        $minQuantity = ($partialNew || $followUp) ? 0 : 1;

        $rules = [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'purchase_order_number' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', 'unique:stock_receipts,reference_number'],
            'received_date' => ['required', 'date'],
            'delivery_date' => ['required', 'date'],
            'status' => ['required', Rule::in([ReceiptStatus::Complete->value, ReceiptStatus::Partial->value])],
            'is_donation' => ['boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.lot_number' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:'.$minQuantity],
            'items.*.manufactured_date' => ['nullable', 'date'],
        ];

        foreach ($this->items as $i => $row) {
            // A line that receives nothing now creates no batch, so it needs no location, cost or expiry.
            $receivesNothing = ($partialNew || $followUp)
                && is_numeric($row['quantity'] ?? null)
                && (int) $row['quantity'] === 0;

            $rules["items.$i.storage_location_id"] = [$receivesNothing ? 'nullable' : 'required', 'exists:storage_locations,id'];
            $rules["items.$i.unit_cost"] = [$receivesNothing ? 'nullable' : 'required', 'numeric', 'min:0'];

            $expiry = ['date'];

            $expiry[] = ! $receivesNothing && in_array((int) $row['inventory_item_id'], $requiresExpiry, true)
                ? 'required'
                : 'nullable';

            if (filled($row['manufactured_date'] ?? null)) {
                $expiry[] = "after_or_equal:items.$i.manufactured_date";
            }

            $rules["items.$i.expiration_date"] = $expiry;

            if ($partialNew) {
                $rules["items.$i.ordered_qty"] = ['required', 'integer', 'min:1'];

                if (is_numeric($row['quantity'] ?? null)) {
                    $rules["items.$i.ordered_qty"][] = "gte:items.$i.quantity";
                }
            }
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        $attributes = [];

        foreach ($this->items as $i => $_) {
            $n = $i + 1;
            $attributes["items.$i.inventory_item_id"] = "item (line $n)";
            $attributes["items.$i.storage_location_id"] = "storage location (line $n)";
            $attributes["items.$i.batch_number"] = "batch number (line $n)";
            $attributes["items.$i.lot_number"] = "lot number (line $n)";
            $attributes["items.$i.ordered_qty"] = "ordered quantity (line $n)";
            $attributes["items.$i.quantity"] = "quantity (line $n)";
            $attributes["items.$i.unit_cost"] = "unit cost (line $n)";
            $attributes["items.$i.manufactured_date"] = "manufactured date (line $n)";
            $attributes["items.$i.expiration_date"] = "expiration date (line $n)";
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'items.*.ordered_qty.gte' => 'The :attribute must be at least the quantity received now.',
        ];
    }

    /**
     * Rules that need the database or span several lines. Throws on the
     * "form.*" keys the views display.
     */
    protected function assertDeliveryRules(?StockReceipts $root): void
    {
        $errors = [];

        if ($this->parent_receipt_id !== null) {
            if ($root === null || $root->isFollowUp() || $root->status !== ReceiptStatus::Partial) {
                throw ValidationException::withMessages([
                    'form.parent_receipt_id' => 'This delivery is already complete or closed.',
                ]);
            }

            $outstanding = app(DeliveryProgress::class)->lines($root)->keyBy(fn ($l) => $l['item']->id);
            $totals = [];

            foreach ($this->items as $i => $row) {
                $itemId = (int) $row['inventory_item_id'];

                if (! $outstanding->has($itemId)) {
                    $errors["form.items.$i.inventory_item_id"] = 'This item is not part of the original delivery.';

                    continue;
                }

                $totals[$itemId] = ($totals[$itemId] ?? 0) + (int) $row['quantity'];

                if ($totals[$itemId] > $outstanding[$itemId]['outstanding']) {
                    $errors["form.items.$i.quantity"] = 'Only '.$outstanding[$itemId]['outstanding'].' still outstanding for this item.';
                }
            }
        } elseif ($this->status === ReceiptStatus::Partial->value) {
            $seen = [];

            foreach ($this->items as $i => $row) {
                $itemId = (int) $row['inventory_item_id'];

                if (isset($seen[$itemId])) {
                    $errors["form.items.$i.inventory_item_id"] = 'This item is already on another line.';
                }

                $seen[$itemId] = true;
            }
        }

        if (collect($this->items)->sum(fn ($row) => (int) $row['quantity']) < 1) {
            $errors['form.items'] = 'Receive at least one item (quantity above 0).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Run every field and cross-line rule without storing, so callers can
     * report these errors before anything else (e.g. attachments).
     */
    public function validateDelivery(): void
    {
        $root = $this->parent_receipt_id !== null ? StockReceipts::find($this->parent_receipt_id) : null;

        if ($this->parent_receipt_id !== null) {
            $this->assertDeliveryRules($root);
        }

        $this->validate();
        $this->assertDeliveryRules($root);
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $attachments
     */
    public function store(array $attachments = []): StockReceipts
    {
        if ($this->parent_receipt_id !== null) {
            $this->assertDeliveryRules(StockReceipts::find($this->parent_receipt_id));
        }

        $this->validate();

        $codes = app(BatchCodeGenerator::class);
        $progress = app(DeliveryProgress::class);

        return DB::transaction(function () use ($attachments, $codes, $progress) {
            $followUp = $this->parent_receipt_id !== null;
            $root = $followUp
                ? StockReceipts::query()->whereKey($this->parent_receipt_id)->lockForUpdate()->first()
                : null;

            $this->assertDeliveryRules($root);

            $status = ReceiptStatus::from($this->status);

            if ($root !== null) {
                $remaining = $progress->lines($root)->mapWithKeys(fn ($l) => [$l['item']->id => $l['outstanding']]);

                foreach ($this->items as $row) {
                    $id = (int) $row['inventory_item_id'];
                    $remaining[$id] = max(0, $remaining[$id] - (int) $row['quantity']);
                }

                $status = $remaining->sum() === 0 ? ReceiptStatus::Complete : ReceiptStatus::Partial;
            }

            $receipt = StockReceipts::create([
                'supplier_id' => $root !== null ? $root->supplier_id : $this->supplier_id,
                'purchase_order_number' => $this->purchase_order_number ?: null,
                'received_by' => Auth::id(),
                'reference_number' => $this->reference_number,
                'received_date' => Date::parse($this->received_date),
                'delivery_date' => Date::parse($this->delivery_date),
                'status' => $status,
                'remarks' => $this->remarks ?: null,
                'parent_receipt_id' => $root?->id,
                'is_donation' => $root !== null ? $root->is_donation : $this->is_donation,
                'completed_at' => $root !== null && $status === ReceiptStatus::Complete ? now() : null,
            ]);

            if (! $followUp && $status === ReceiptStatus::Partial) {
                foreach ($this->items as $row) {
                    StockReceiptLines::create([
                        'stock_receipt_id' => $receipt->id,
                        'inventory_item_id' => $row['inventory_item_id'],
                        'ordered_qty' => $row['ordered_qty'],
                    ]);
                }
            }

            foreach ($this->items as $row) {
                if ((int) $row['quantity'] < 1) {
                    continue;
                }

                $batch = $receipt->inventoryBatches()->create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'storage_location_id' => $row['storage_location_id'],
                    'batch_number' => filled($row['batch_number'] ?? null)
                        ? trim($row['batch_number'])
                        : $codes->batchNumber(),
                    'lot_number' => filled($row['lot_number'] ?? null)
                        ? trim($row['lot_number'])
                        : $codes->lotNumber(),
                    'received_qty' => $row['quantity'],
                    'remaining_qty' => $row['quantity'],
                    'manufactured_date' => $row['manufactured_date'] ?: null,
                    'expiration_date' => $row['expiration_date'] ?: null,
                    'received_at' => now(),
                    'unit_cost' => $row['unit_cost'],
                ]);

                $balance = InventoryBatches::query()
                    ->where('inventory_item_id', $row['inventory_item_id'])
                    ->sum('remaining_qty');

                InventoryTransactions::create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'batch_id' => $batch->id,
                    'user_id' => Auth::id(),
                    'type' => InventoryTransactionType::Receipt,
                    'quantity' => $row['quantity'],
                    'balance_after' => $balance,
                    'reference_type' => StockReceipts::class,
                    'reference_id' => $receipt->id,
                ]);
            }

            foreach ($attachments as $file) {
                $receipt->attachments()->create([
                    'path' => $file->store('stock-receipts', 'public'),
                ]);
            }

            if ($root !== null) {
                $progress->refresh($root);
            } elseif ($status === ReceiptStatus::Partial) {
                $progress->refresh($receipt);
            }

            return $receipt;
        });
    }
}
