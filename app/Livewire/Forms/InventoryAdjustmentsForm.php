<?php

namespace App\Livewire\Forms;

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Records a new inventory adjustment. Adjustments are append-only, so this
 * form only ever creates records; there is intentionally no update().
 */
class InventoryAdjustmentsForm extends Form
{
    public $batch_id = '';

    public string $type = '';

    public $quantity = '';

    public string $reason = '';

    public string $requested_by = '';

    public string $approved_by = '';

    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->requested_by = (string) Auth::user()?->name;
    }

    public function adjustmentType(): ?AdjustmentType
    {
        return AdjustmentType::tryFrom($this->type);
    }

    /**
     * Work out [previous, change, new] for the given batch quantity.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    public function calculate(int $previous): array
    {
        $qty = (int) $this->quantity;

        $new = match ($this->adjustmentType()?->direction()) {
            'decrease' => $previous - $qty,
            'increase' => $previous + $qty,
            'set' => $qty,
            default => $previous,
        };

        return [$previous, $new - $previous, $new];
    }

    protected function rules(): array
    {
        return [
            'batch_id' => ['required', 'exists:inventory_batches,id'],
            'type' => ['required', Rule::enum(AdjustmentType::class)],
            'quantity' => [
                'required',
                'integer',
                $this->adjustmentType() === AdjustmentType::CountCorrection ? 'min:0' : 'min:1',
            ],
            'reason' => ['required', 'string', 'max:1000'],
            'requested_by' => ['required', 'string', 'max:255'],
            'approved_by' => ['required', 'string', 'max:255'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'batch_id' => 'batch',
            'type' => 'adjustment type',
            'requested_by' => 'requested by',
            'approved_by' => 'approved by',
        ];
    }

    public function store(): InventoryAdjustments
    {
        $this->validate();

        return DB::transaction(function () {
            // Re-read the batch inside the transaction so "previous qty" is the
            // real current balance, not whatever the form showed earlier.
            $batch = InventoryBatches::query()->lockForUpdate()->findOrFail($this->batch_id);

            [$previous, $change, $new] = $this->calculate($batch->remaining_qty);

            $this->guard($batch, $change, $new);

            $adjustment = InventoryAdjustments::create([
                'batch_id' => $batch->id,
                'user_id' => Auth::id(),
                'type' => $this->type,
                'requested_by' => trim($this->requested_by),
                'approved_by' => trim($this->approved_by),
                'previous_qty' => $previous,
                'changed_qty' => $change,
                'new_qty' => $new,
                'reason' => trim($this->reason),
            ]);

            $batch->update(['remaining_qty' => $new]);

            InventoryTransactions::create([
                'inventory_item_id' => $batch->inventory_item_id,
                'batch_id' => $batch->id,
                'user_id' => Auth::id(),
                'type' => InventoryTransactionType::Adjustment,
                'quantity' => $change,
                'balance_after' => InventoryBatches::query()
                    ->where('inventory_item_id', $batch->inventory_item_id)
                    ->sum('remaining_qty'),
                'reference_type' => InventoryAdjustments::class,
                'reference_id' => $adjustment->id,
            ]);

            return $adjustment;
        });
    }

    protected function guard(InventoryBatches $batch, int $change, int $new): void
    {
        if ($new < 0) {
            throw ValidationException::withMessages([
                'form.quantity' => "Only {$batch->remaining_qty} remain in this batch.",
            ]);
        }

        if ($change === 0) {
            throw ValidationException::withMessages([
                'form.quantity' => 'This adjustment does not change the quantity.',
            ]);
        }

        if ($this->adjustmentType() === AdjustmentType::Return && $new > $batch->received_qty) {
            throw ValidationException::withMessages([
                'form.quantity' => 'A return cannot bring the batch above the '
                    .number_format($batch->received_qty).' originally received.',
            ]);
        }
    }
}
