<?php

namespace Database\Factories;

use App\Enums\AdjustmentType;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds an adjustment record whose previous / changed / new quantities agree
 * with the batch's current remaining_qty and the adjustment type's direction.
 *
 * Note: the factory only writes the adjustment row. It does not update the
 * batch or log an inventory transaction; InventoryAdjustmentsSeeder does that.
 *
 * @extends Factory<InventoryAdjustments>
 */
class InventoryAdjustmentsFactory extends Factory
{
    protected $model = InventoryAdjustments::class;

    /**
     * @var array<string, list<string>>
     */
    public const REASONS = [
        'damage' => [
            'Water damage from roof leak in storage area.',
            'Units crushed during shelf reorganization.',
            'Packaging torn, contents contaminated.',
            'Dropped while unloading, items unusable.',
        ],
        'loss' => [
            'Missing during monthly stock count.',
            'Unaccounted for after office transfer.',
            'Not found at assigned storage location.',
        ],
        'expiry_write_off' => [
            'Batch passed expiration date, disposed per policy.',
            'Expired stock pulled from shelf during inspection.',
        ],
        'count_correction' => [
            'Physical count did not match system record.',
            'Correction after quarterly inventory audit.',
            'Encoding error on original receipt corrected.',
        ],
        'return' => [
            'Unused items returned by requesting office.',
            'Over-issued quantity returned to stock.',
        ],
    ];

    public function definition(): array
    {
        return [
            'batch_id' => InventoryBatches::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([
                AdjustmentType::Damage,
                AdjustmentType::Loss,
                AdjustmentType::CountCorrection,
            ]),
            'requested_by' => fake()->name(),
            'approved_by' => fake()->name(),
            'previous_qty' => fn (array $a) => $this->batch($a)->remaining_qty,
            'changed_qty' => fn (array $a) => $this->change(AdjustmentType::from($this->typeValue($a)), $this->batch($a)),
            'new_qty' => fn (array $a) => $a['previous_qty'] + $a['changed_qty'],
            'reason' => fn (array $a) => fake()->randomElement(self::REASONS[$this->typeValue($a)]),
        ];
    }

    public function damage(): static
    {
        return $this->state(['type' => AdjustmentType::Damage]);
    }

    public function loss(): static
    {
        return $this->state(['type' => AdjustmentType::Loss]);
    }

    public function expiryWriteOff(): static
    {
        return $this->state(['type' => AdjustmentType::ExpiryWriteOff]);
    }

    public function countCorrection(): static
    {
        return $this->state(['type' => AdjustmentType::CountCorrection]);
    }

    public function returned(): static
    {
        return $this->state(['type' => AdjustmentType::Return]);
    }

    /**
     * Signed change for the type, kept within the same limits as InventoryAdjustmentsForm:
     * never below zero and a return never above the received quantity.
     */
    public static function change(AdjustmentType $type, InventoryBatches $batch): int
    {
        $remaining = $batch->remaining_qty;
        $headroom = $batch->received_qty - $remaining;

        return match ($type) {
            AdjustmentType::Damage, AdjustmentType::Loss => -fake()->numberBetween(1, max(1, min($remaining, (int) ceil($remaining * 0.2)))),
            AdjustmentType::ExpiryWriteOff => -$remaining,
            // Nothing to return into a full batch; treat it as +0 rather than overshooting.
            AdjustmentType::Return => $headroom > 0 ? fake()->numberBetween(1, $headroom) : 0,
            AdjustmentType::CountCorrection => $remaining === 0
                ? fake()->numberBetween(1, 5)
                : fake()->randomElement([-1, 1]) * fake()->numberBetween(1, min(5, $remaining)),
        };
    }

    protected function batch(array $attributes): InventoryBatches
    {
        return InventoryBatches::query()->findOrFail($attributes['batch_id']);
    }

    protected function typeValue(array $attributes): string
    {
        $type = $attributes['type'];

        return $type instanceof AdjustmentType ? $type->value : $type;
    }
}
