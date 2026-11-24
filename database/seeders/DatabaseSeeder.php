<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Inventory\StockStatusMonitor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Vincent Eleazar Uykieng',
            'email' => 'vincenteleazar.uykieng04@gmail.com',
            'password' => Hash::make('testtest'),
        ]);

        $monitor = app(StockStatusMonitor::class);

        // Seeded stock is history, not news: send no stock alerts for it.
        $monitor->muted(fn () => $this->call([
            StorageLocationsSeeder::class,
            SuppliersSeeder::class,
            InventoryCategoriesSeeder::class,
            InventoryItemsSeeder::class,
            StockReceiptsSeeder::class,
            InventoryBatchesSeeder::class,
            InventoryAdjustmentsSeeder::class,
        ]));

        // Store each item's current status so the first real stock movement
        // is compared against the seeded state.
        $monitor->baseline();
    }
}
