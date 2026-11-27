<?php

namespace Database\Factories;

use App\Enums\PropertyClass;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\IssuedPropertyUnits;
use App\Models\RequisitionItems;
use App\Models\StockIssuanceBatches;
use App\Models\StockIssuanceItems;
use App\Models\StockIssuances;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds the whole issuance chain (issuance → line → batch draw) for a unit
 * unless a stock_issuance_batch_id is given.
 *
 * @extends Factory<IssuedPropertyUnits>
 */
class IssuedPropertyUnitsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItems::factory()->property(),
            'stock_issuance_batch_id' => fn (array $attributes) => $this->issuanceBatchFor((int) $attributes['inventory_item_id'], (string) $attributes['unit_cost']),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-####')),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN-########')),
            'unit_cost' => '1196.11',
            'property_class' => PropertyClass::Splv,
        ];
    }

    /**
     * A unit of the given class at a cost inside that class's default band.
     */
    public function ofClass(PropertyClass $class): static
    {
        return $this->state(fn (): array => [
            'property_class' => $class,
            'unit_cost' => match ($class) {
                PropertyClass::Ppe => '78000.00',
                PropertyClass::Sphv => '6000.00',
                PropertyClass::Splv, PropertyClass::Consumable => '1196.11',
            },
        ]);
    }

    private function issuanceBatchFor(int $itemId, string $unitCost): int
    {
        $batch = InventoryBatches::factory()->create(['inventory_item_id' => $itemId, 'unit_cost' => $unitCost]);
        $issuance = StockIssuances::factory()->create();

        $line = RequisitionItems::factory()->create([
            'requisition_id' => $issuance->requisition_id,
            'inventory_item_id' => $itemId,
            'requested_qty' => 1,
            'approved_qty' => 1,
            'released_qty' => 1,
        ]);

        $issuanceItem = StockIssuanceItems::create([
            'stock_issuance_id' => $issuance->id,
            'requisition_item_id' => $line->id,
            'released_qty' => 1,
        ]);

        return StockIssuanceBatches::create([
            'stock_issuance_item_id' => $issuanceItem->id,
            'batch_id' => $batch->id,
            'quantity' => 1,
        ])->id;
    }
}
