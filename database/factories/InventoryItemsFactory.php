<?php

namespace Database\Factories;

use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItems>
 */
class InventoryItemsFactory extends Factory
{
    protected $model = InventoryItems::class;

    public function configure(): static
    {
        return $this->afterCreating(function (InventoryItems $item) {

            $categories = InventoryCategories::where('is_active', true)
                ->inRandomOrder()
                ->take(fake()->numberBetween(1, 2))
                ->pluck('id');

            $item->inventoryCategory()->attach($categories);

        });
    }

    public function definition(): array
    {
        $minimumStock = fake()->numberBetween(5, 30);

        return [
            'item_code' => 'INV-'.fake()->unique()->numerify('####'),
            'name' => fake()->unique()->words(2, true),
            'unit' => fake()->randomElement([
                'Piece', 'Box', 'Pack', 'Bottle', 'Ream', 'Roll', 'Meter',
            ]),

            'requires_expiry' => false,

            'minimum_stock' => $minimumStock,
            'reorder_level' => fake()->numberBetween(
                $minimumStock + 5,
                $minimumStock + 30
            ),

            'is_active' => fake()->boolean(95),
        ];
    }
}
