<?php

namespace Database\Seeders;

use App\Models\User;
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

        $this->call([
            StorageLocationsSeeder::class,
            SuppliersSeeder::class,
            InventoryCategoriesSeeder::class,
            InventoryItemsSeeder::class,
        ]);

    }
}
