<?php

namespace App\Livewire\Forms;

use App\Models\InventoryBatches;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InventoryBatchesForm extends Form
{
    public ?InventoryBatches $inventoryBatch;

    #[Validate('required|integer')]
    public int $inventory_item_id = 0;

    #[Validate('required|integer')]
    public int $stock_receipt_id = 0;

    #[Validate('required|integer')]
    public int $storage_location_id = 0;

    #[Validate('required|string|max:255')]
    public string $batch_number = '';

    #[Validate('required|integer|min:0')]
    public int $received_qty = 0;

    #[Validate('required|integer|min:0')]
    public int $remaining_qty = 0;

    #[Validate('nullable|date')]
    public ?string $manufactured_date = null;

    #[Validate('nullable|date')]
    public ?string $expiration_date = null;

    #[Validate('nullable|date')]
    public ?string $received_at = null;

    #[Validate('required|numeric|min:0')]
    public float $unit_cost = 0.00;

    public function store()
    {
        $this->validate();

        InventoryBatches::create([
            'inventory_item_id' => $this->inventory_item_id,
            'stock_receipt_id' => $this->stock_receipt_id,
            'storage_location_id' => $this->storage_location_id,
            'batch_number' => $this->batch_number,
            'received_qty' => $this->received_qty,
            'remaining_qty' => $this->remaining_qty,
            'manufactured_date' => $this->manufactured_date,
            'expiration_date' => $this->expiration_date,
            'received_at' => $this->received_at,
            'unit_cost' => $this->unit_cost,
        ]);

        $this->reset();
    }

    public function update()
    {
        $this->validate();

        $this->inventoryBatch->update([
            'inventory_item_id' => $this->inventory_item_id,
            'stock_receipt_id' => $this->stock_receipt_id,
            'storage_location_id' => $this->storage_location_id,
            'batch_number' => $this->batch_number,
            'received_qty' => $this->received_qty,
            'remaining_qty' => $this->remaining_qty,
            'manufactured_date' => $this->manufactured_date,
            'expiration_date' => $this->expiration_date,
            'received_at' => $this->received_at,
            'unit_cost' => $this->unit_cost,
        ]);
    }
}
