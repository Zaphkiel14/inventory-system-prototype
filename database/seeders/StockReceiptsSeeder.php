<?php

namespace Database\Seeders;

use App\Models\StockReceipts;
use App\Models\Suppliers;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Receipt headers (deliveries) spread over the last six months.
 * Their line items / batches are created by InventoryBatchesSeeder.
 */
class StockReceiptsSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Suppliers::pluck('id');
        $users = User::pluck('id');

        if ($suppliers->isEmpty()) {
            $suppliers = Suppliers::factory()->count(5)->create()->pluck('id');
        }

        if ($users->isEmpty()) {
            $users = User::factory()->count(1)->create()->pluck('id');
        }

        StockReceipts::factory()
            ->count(25)
            ->state(fn () => [
                'supplier_id' => $suppliers->random(),
                'received_by' => $users->random(),
            ])
            ->create();
    }
}
