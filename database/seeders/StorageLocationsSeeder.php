<?php

namespace Database\Seeders;

use App\Models\StorageLocations;
use Illuminate\Database\Seeder;

class StorageLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            // Ground Floor
            ['name' => 'Shelf 1 - Ground Floor', 'type' => 'Shelf'],
            ['name' => 'Shelf 2 - Ground Floor', 'type' => 'Shelf'],
            ['name' => 'Shelf 3 - Ground Floor', 'type' => 'Shelf'],
            ['name' => 'Cabinet 1 - Ground Floor', 'type' => 'Cabinet'],
            ['name' => 'Cabinet 2 - Ground Floor', 'type' => 'Cabinet'],
            ['name' => 'Storage Rack 1 - Ground Floor', 'type' => 'Storage Rack'],

            // Second Floor
            ['name' => 'Shelf 1 - Second Floor', 'type' => 'Shelf'],
            ['name' => 'Shelf 2 - Second Floor', 'type' => 'Shelf'],
            ['name' => 'Shelf 3 - Second Floor', 'type' => 'Shelf'],
            ['name' => 'Cabinet 1 - Second Floor', 'type' => 'Cabinet'],
            ['name' => 'Storage Rack 1 - Second Floor', 'type' => 'Storage Rack'],

            // Third Floor
            ['name' => 'Shelf 1 - Third Floor', 'type' => 'Shelf'],
            ['name' => 'Shelf 2 - Third Floor', 'type' => 'Shelf'],
            ['name' => 'Cabinet 1 - Third Floor', 'type' => 'Cabinet'],

            // Storage Rooms
            ['name' => 'Main Storage Room', 'type' => 'Storage Room'],
            ['name' => 'Secondary Storage Room', 'type' => 'Storage Room'],
        ];

        foreach ($locations as $location) {
            StorageLocations::factory()->create($location);
        }
    }
}
