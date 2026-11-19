<?php

namespace Database\Factories;

use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * A batch is one line of a stock receipt: the on-hand stock that FIFO / FEFO
 * issuance draws from. Expiry dates are only set for items that require them.
 *
 * @extends Factory<InventoryBatches>
 */
class InventoryBatchesFactory extends Factory
{
    protected $model = InventoryBatches::class;

    public function definition(): array
    {
        $qty = fake()->numberBetween(10, 200);

        return [
            'inventory_item_id' => InventoryItems::factory(),
            'stock_receipt_id' => StockReceipts::factory(),
            'storage_location_id' => StorageLocations::factory(),
            'batch_number' => 'BAT-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'lot_number' => 'LOT-'.now()->format('Ymd').'-'.fake()->unique()->numerify('#####'),
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'received_at' => fn (array $attributes) => $this->receivedAt($attributes),
            'manufactured_date' => fn (array $attributes) => $this->requiresExpiry($attributes)
                ? Carbon::parse($attributes['received_at'])->subDays(fake()->numberBetween(15, 180))->toDateString()
                : null,
            'expiration_date' => fn (array $attributes) => $this->requiresExpiry($attributes)
                ? today()->addDays(fake()->numberBetween(60, 730))->toDateString()
                : null,
            'unit_cost' => fake()->randomFloat(2, 5, 2500),
            'created_at' => fn (array $attributes) => $attributes['received_at'],
            'updated_at' => fn (array $attributes) => $attributes['received_at'],
        ];
    }

    /**
     * Some of the batch has already been issued or written off.
     */
    public function partiallyConsumed(): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_qty' => fake()->numberBetween(1, max(1, $attributes['received_qty'] - 1)),
        ]);
    }

    public function depleted(): static
    {
        return $this->state(['remaining_qty' => 0]);
    }

    /**
     * Past its expiration date; excluded from the `usable` scope.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'manufactured_date' => today()->subDays(fake()->numberBetween(400, 700))->toDateString(),
            'expiration_date' => today()->subDays(fake()->numberBetween(1, 60))->toDateString(),
        ]);
    }

    /**
     * Expires within the next 30 days, so FEFO picks it first.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'expiration_date' => today()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        ]);
    }

    protected function receivedAt(array $attributes): Carbon
    {
        $date = StockReceipts::query()->whereKey($attributes['stock_receipt_id'])->value('received_date');

        return $date ? Carbon::parse($date) : now();
    }

    protected function requiresExpiry(array $attributes): bool
    {
        return (bool) InventoryItems::query()
            ->whereKey($attributes['inventory_item_id'])
            ->value('requires_expiry');
    }
}
