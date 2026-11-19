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
        $items = [
            ['name' => 'A4 Copy Paper', 'unit' => 'Ream', 'expiry' => false],
            ['name' => 'Legal Size Bond Paper', 'unit' => 'Ream', 'expiry' => false],
            ['name' => 'Ballpoint Pen - Blue', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Ballpoint Pen - Black', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Permanent Marker', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Whiteboard Marker', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Manila Folder', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Expanding Envelope', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Stapler', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Staple Wire', 'unit' => 'Box', 'expiry' => false],
            ['name' => 'Packaging Tape', 'unit' => 'Roll', 'expiry' => false],
            ['name' => 'Correction Tape', 'unit' => 'Piece', 'expiry' => false],

            ['name' => 'Printer Ink Cartridge - Black', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Printer Ink Cartridge - Cyan', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Printer Toner Cartridge', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'USB Flash Drive 32GB', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Wireless Mouse', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Computer Keyboard', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'HDMI Cable', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Network Cable CAT6', 'unit' => 'Meter', 'expiry' => false],

            ['name' => 'LED Bulb 12W', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Extension Cord', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Electrical Tape', 'unit' => 'Roll', 'expiry' => false],

            ['name' => 'Disinfectant Solution', 'unit' => 'Bottle', 'expiry' => true],
            ['name' => 'Alcohol 70%', 'unit' => 'Bottle', 'expiry' => true],
            ['name' => 'Liquid Hand Soap', 'unit' => 'Bottle', 'expiry' => true],
            ['name' => 'All-Purpose Cleaner', 'unit' => 'Bottle', 'expiry' => true],
            ['name' => 'Garbage Bags', 'unit' => 'Pack', 'expiry' => false],
            ['name' => 'Facial Tissue', 'unit' => 'Box', 'expiry' => false],

            ['name' => 'First Aid Bandage', 'unit' => 'Pack', 'expiry' => true],
            ['name' => 'Sterile Gauze', 'unit' => 'Pack', 'expiry' => true],
            ['name' => 'Disposable Gloves', 'unit' => 'Box', 'expiry' => true],
            ['name' => 'Surgical Mask', 'unit' => 'Box', 'expiry' => false],

            ['name' => 'Office Chair', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Office Table', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Filing Cabinet', 'unit' => 'Piece', 'expiry' => false],
            ['name' => 'Bookshelf', 'unit' => 'Piece', 'expiry' => false],
        ];

        $item = fake()->randomElement($items);

        $minimumStock = fake()->numberBetween(5, 30);

        return [
            'item_code' => 'INV-'.fake()->unique()->numerify('####'),
            'name' => $item['name'],
            'unit' => $item['unit'],

            'allocation_method' => $item['expiry']
                ? 'FEFO'
                : fake()->randomElement(['FIFO', 'FEFO']),

            'requires_expiry' => $item['expiry'],

            'minimum_stock' => $minimumStock,
            'reorder_level' => fake()->numberBetween(
                $minimumStock + 5,
                $minimumStock + 30
            ),

            'is_active' => fake()->boolean(95),
        ];
    }
}
