<?php

namespace App\Livewire\Forms;

use App\Enums\InventoryTransactionType;
use App\Enums\ReceiptStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function initialize(): void
    {
        $this->reset();
        $this->received_date = today()->toDateString();
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'inventory_item_id' => '',
            'storage_location_id' => '',
            'batch_number' => '',
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

        $rules = [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_order_number' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['required', 'string', 'max:255', 'unique:stock_receipts,reference_number'],
            'received_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ReceiptStatus::class)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'exists:inventory_items,id'],
            'items.*.storage_location_id' => ['required', 'exists:storage_locations,id'],
            'items.*.batch_number' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.manufactured_date' => ['nullable', 'date'],
        ];

        foreach ($this->items as $i => $row) {
            $expiry = ['date'];

            $expiry[] = in_array((int) $row['inventory_item_id'], $requiresExpiry, true)
                ? 'required'
                : 'nullable';

            if (filled($row['manufactured_date'] ?? null)) {
                $expiry[] = "after_or_equal:items.$i.manufactured_date";
            }

            $rules["items.$i.expiration_date"] = $expiry;
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
            $attributes["items.$i.quantity"] = "quantity (line $n)";
            $attributes["items.$i.unit_cost"] = "unit cost (line $n)";
            $attributes["items.$i.manufactured_date"] = "manufactured date (line $n)";
            $attributes["items.$i.expiration_date"] = "expiration date (line $n)";
        }

        return $attributes;
    }

    /**
     * @param  array<int, TemporaryUploadedFile>  $attachments
     */
    public function store(array $attachments = []): StockReceipts
    {
        $this->validate();

        return DB::transaction(function () use ($attachments) {
            $receipt = StockReceipts::create([
                'supplier_id' => $this->supplier_id,
                'purchase_order_number' => $this->purchase_order_number ?: null,
                'received_by' => Auth::id(),
                'reference_number' => $this->reference_number,
                'received_date' => $this->received_date,
                'delivery_date' => $this->delivery_date ?: null,
                'status' => $this->status,
                'remarks' => $this->remarks ?: null,
            ]);

            foreach ($this->items as $row) {
                $batch = $receipt->inventoryBatches()->create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'storage_location_id' => $row['storage_location_id'],
                    'batch_number' => $row['batch_number'],
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

            return $receipt;
        });
    }
}
