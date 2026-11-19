<?php

namespace Database\Seeders;

use App\Models\InventoryCategories;
use Illuminate\Database\Seeder;

class InventoryCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        InventoryCategories::factory()->count(10)->create();
    }
}
