<?php

namespace App\Livewire\Forms;

use App\Models\InventoryTransactions;
use Livewire\Attributes\Validate;
use Livewire\Form;

class InventoryTransactionsForm extends Form
{
    public ?InventoryTransactions $inventoryTransaction;

    #[Validate('required|integer')]
    public int $batch_id = 0;

    #[Validate('required|integer')]
    public int $user_id = 0;

    #[Validate('nullable|integer')]
    public ?int $approved_by = null;

    #[Validate('required|integer')]
    public int $previous_qty = 0;

    #[Validate('required|integer')]
    public int $changed_qty = 0;

    #[Validate('required|integer')]
    public int $new_qty = 0;

    #[Validate('required|string|max:255')]
    public string $reason = '';

    public function store()
    {
        $this->validate();

        InventoryTransactions::create([
            'batch_id' => $this->batch_id,
            'user_id' => $this->user_id,
            'approved_by' => $this->approved_by,
            'previous_qty' => $this->previous_qty,
            'changed_qty' => $this->changed_qty,
            'new_qty' => $this->new_qty,
            'reason' => $this->reason,
        ]);

        $this->reset();
    }

    public function update()
    {
        $this->validate();

        $this->inventoryTransaction->update([
            'batch_id' => $this->batch_id,
            'user_id' => $this->user_id,
            'approved_by' => $this->approved_by,
            'previous_qty' => $this->previous_qty,
            'changed_qty' => $this->changed_qty,
            'new_qty' => $this->new_qty,
            'reason' => $this->reason,
        ]);
    }
}
