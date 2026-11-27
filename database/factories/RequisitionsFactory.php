<?php

namespace Database\Factories;

use App\Enums\RequisitionStatus;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Requisitions at a given stage. Lines are added with withLine(), and their
 * quantities follow the requisition's status (pending lines have nothing
 * approved yet; approved lines have everything outstanding).
 *
 * These are fixtures only: no state here moves stock or writes the ledger.
 * To test an actual release, go through IssuancesForm.
 *
 * @extends Factory<Requisitions>
 */
class RequisitionsFactory extends Factory
{
    protected $model = Requisitions::class;

    public function definition(): array
    {
        return [
            'request_number' => fake()->unique()->numerify('REQ-FAC-######'),
            'user_id' => User::factory(),
            'status' => RequisitionStatus::Pending,
            'purpose' => fake()->randomElement([
                'Monthly supplies',
                'Ward replenishment',
                'Office supplies for the quarter',
                'Equipment for the new treatment room',
            ]),
            'submitted_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => RequisitionStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state(['status' => RequisitionStatus::Approved]);
    }

    /**
     * Approved and partly released. Give each line a released quantity
     * below its approved quantity through withLine().
     */
    public function partiallyIssued(): static
    {
        return $this->state(['status' => RequisitionStatus::PartiallyIssued]);
    }

    public function issued(): static
    {
        return $this->state(['status' => RequisitionStatus::Issued]);
    }

    /**
     * Add a requested line. For an approved (or later) requisition, $qty is
     * also the approved quantity; $released defaults to all of it when the
     * requisition is Issued and to none otherwise.
     */
    public function withLine(InventoryItems $item, int $qty, ?int $released = null): static
    {
        return $this->afterCreating(function (Requisitions $requisition) use ($item, $qty, $released): void {
            $line = RequisitionItemsFactory::new()->for($requisition, 'requisition')->for($item, 'inventoryItem');

            $line = match ($requisition->getAttribute('status')) {
                RequisitionStatus::Pending,
                RequisitionStatus::Rejected,
                RequisitionStatus::Cancelled => $line->pending($qty),
                RequisitionStatus::Issued => $line->approved($qty, released: $released ?? $qty),
                default => $line->approved($qty, released: $released ?? 0),
            };

            $line->create();
        });
    }
}
