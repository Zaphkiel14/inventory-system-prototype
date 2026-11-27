<?php

namespace Database\Factories;

use App\Models\IssuedPropertyUnits;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A hand-encoded line (no issued unit) on a draft SPLV ICS. Use forUnit() to
 * put an issued property unit on the line.
 *
 * @extends Factory<PropertyDocumentLines>
 */
class PropertyDocumentLinesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_document_id' => PropertyDocuments::factory(),
            'issued_property_unit_id' => null,
            'inventory_item_id' => null,
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_cost' => '1196.11',
            'item_name' => ucfirst(fake()->word().' '.fake()->word()),
            'item_type' => null,
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-####')),
            'serial_number' => strtoupper(fake()->unique()->bothify('SN-########')),
            'property_number' => null,
            'estimated_useful_life_years' => 5,
            'date_acquired' => today()->subMonth()->toDateString(),
        ];
    }

    /**
     * Copy an issued unit onto the line, as the drafter does.
     */
    public function forUnit(IssuedPropertyUnits $unit): static
    {
        return $this->state(fn (): array => [
            'issued_property_unit_id' => $unit->id,
            'inventory_item_id' => $unit->inventory_item_id,
            'unit_cost' => $unit->unit_cost,
            'brand' => $unit->brand,
            'model' => $unit->model,
            'serial_number' => $unit->serial_number,
        ]);
    }
}
