<?php

namespace Database\Factories;

use App\Models\InventoryCategories;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryCategories>
 */
class InventoryCategoriesFactory extends Factory
{
    protected $model = InventoryCategories::class;

    public function definition(): array
    {
        $categories = [
            [
                'name' => 'Office Supplies',
                'description' => 'General office materials including paper, pens, folders, and other daily office necessities.',
            ],
            [
                'name' => 'Information Technology Equipment',
                'description' => 'Computers, printers, networking devices, and other information technology equipment.',
            ],
            [
                'name' => 'Furniture and Fixtures',
                'description' => 'Office furniture such as tables, chairs, cabinets, and storage fixtures.',
            ],
            [
                'name' => 'Janitorial Supplies',
                'description' => 'Cleaning materials, sanitation products, and general housekeeping supplies.',
            ],
            [
                'name' => 'Electrical Supplies',
                'description' => 'Electrical components, cables, lighting equipment, and related materials.',
            ],
            [
                'name' => 'Medical Supplies',
                'description' => 'First aid materials, medical consumables, and basic healthcare supplies.',
            ],
            [
                'name' => 'Laboratory Supplies',
                'description' => 'Laboratory equipment, instruments, and consumable materials.',
            ],
            [
                'name' => 'Tools and Equipment',
                'description' => 'Maintenance tools, repair equipment, and general-purpose instruments.',
            ],
            [
                'name' => 'Printing Supplies',
                'description' => 'Printer ink, toner cartridges, printing paper, and related materials.',
            ],
            [
                'name' => 'Safety and Security Supplies',
                'description' => 'Personal protective equipment, safety devices, and security materials.',
            ],
        ];

        $category = fake()->unique()->randomElement($categories);

        return [
            'name' => $category['name'],
            'description' => $category['description'],
            'is_active' => fake()->boolean(95),
        ];
    }
}
