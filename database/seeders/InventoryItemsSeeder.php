<?php

namespace Database\Seeders;

use App\Models\InventoryItems;
use Illuminate\Database\Seeder;

class InventoryItemsSeeder extends Seeder
{
    public function run(): void
    {
        InventoryItems::factory()->count(100)->create();
    }
}
