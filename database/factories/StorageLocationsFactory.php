<?php

namespace Database\Factories;

use App\Models\StorageLocations;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageLocations>
 */
class StorageLocationsFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([
            'Shelf',
            'Cabinet',
            'Storage Rack',
            'Storage Room',
        ]);

        return [
            'name' => $type.' '.fake()->unique()->numberBetween(1, 20),
            'type' => $type,
        ];
    }
}
