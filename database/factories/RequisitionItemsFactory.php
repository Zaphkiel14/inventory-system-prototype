<?php

namespace Database\Factories;

use App\Models\InventoryItems;
use App\Models\RequisitionItems;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * One requested line, with quantities for the stage it has reached.
 *
 * @extends Factory<RequisitionItems>
 */
class RequisitionItemsFactory extends Factory
{
    protected $model = RequisitionItems::class;

    public function definition(): array
    {
        return [
            'requisition_id' => RequisitionsFactory::new(),
            'inventory_item_id' => InventoryItems::factory(),
            'requested_qty' => 1,
            'available_qty' => 0,
            'approved_qty' => null,
            'released_qty' => 0,
            'outstanding_qty' => 0,
        ];
    }

    /**
     * Requested and waiting for approval: nothing approved or committed yet.
     */
    public function pending(int $qty): static
    {
        return $this->state([
            'requested_qty' => $qty,
            'available_qty' => 0,
            'approved_qty' => null,
            'released_qty' => 0,
            'outstanding_qty' => 0,
        ]);
    }

    /**
     * Approved for $qty, of which $released has been issued; the rest is
     * outstanding (committed stock).
     */
    public function approved(int $qty, int $released = 0): static
    {
        if ($released < 0 || $released > $qty) {
            throw new InvalidArgumentException("Released quantity [{$released}] must be between 0 and the approved quantity [{$qty}].");
        }

        return $this->state([
            'requested_qty' => $qty,
            'available_qty' => $qty,
            'approved_qty' => $qty,
            'released_qty' => $released,
            'outstanding_qty' => $qty - $released,
        ]);
    }
}
