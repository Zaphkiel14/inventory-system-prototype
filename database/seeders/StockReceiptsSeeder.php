<?php

namespace Database\Seeders;

use App\Enums\ReceiptStatus;
use App\Models\InventoryItems;
use App\Models\StockReceiptLines;
use App\Models\StockReceipts;
use App\Models\Suppliers;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Receipt headers (deliveries) spread over the last six months, including a
 * few donations and partial deliveries (with their follow-ups).
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

        $base = fn () => [
            'supplier_id' => $suppliers->random(),
            'received_by' => $users->random(),
        ];

        StockReceipts::factory()->count(21)->complete()->state($base)->create();
        StockReceipts::factory()->count(2)->complete()->donation()->state($base)->create();

        $items = InventoryItems::where('is_active', true)->inRandomOrder()->limit(12)->get();

        if ($items->count() < 3) {
            return;
        }

        /** @var list<array{int, string, bool}> $scenarios [days ago the original arrived, scenario, donation?] */
        $scenarios = [
            [45, 'completed', false],
            [30, 'outstanding', false],
            [20, 'outstanding_after_follow_up', false],
            [15, 'closed', false],
            [12, 'outstanding', true],
        ];

        foreach ($scenarios as $n => [$daysAgo, $scenario, $donation]) {
            $attributes = $base();
            $chosen = $items->slice(($n * 2) % max(1, $items->count() - 2), 3);

            $root = StockReceipts::factory()
                ->partial()
                ->receivedOn(now()->subDays($daysAgo))
                ->state([...$attributes, 'is_donation' => $donation, 'purchase_order_number' => $donation ? null : 'PO-'.now()->format('Y').'-9'.$n.'001'])
                ->create();

            foreach ($chosen as $item) {
                StockReceiptLines::create([
                    'stock_receipt_id' => $root->id,
                    'inventory_item_id' => $item->id,
                    'ordered_qty' => fake()->numberBetween(20, 100),
                ]);
            }

            if (in_array($scenario, ['completed', 'outstanding_after_follow_up'], true)) {
                StockReceipts::factory()
                    ->receivedOn(now()->subDays((int) ($daysAgo / 2)))
                    ->state([
                        ...$attributes,
                        'parent_receipt_id' => $root->id,
                        'is_donation' => $donation,
                        'purchase_order_number' => $root->purchase_order_number,
                        'status' => $scenario === 'completed' ? ReceiptStatus::Complete : ReceiptStatus::Partial,
                        'completed_at' => $scenario === 'completed' ? now()->subDays((int) ($daysAgo / 2)) : null,
                    ])
                    ->create();
            }

            if ($scenario === 'completed') {
                $root->update(['status' => ReceiptStatus::Complete, 'completed_at' => now()->subDays((int) ($daysAgo / 2))]);
            }

            if ($scenario === 'closed') {
                $root->update([
                    'status' => ReceiptStatus::Closed,
                    'completed_at' => now()->subDays(5),
                    'closed_reason' => 'Supplier could not fulfil the balance of the order.',
                ]);
            }
        }
    }
}
