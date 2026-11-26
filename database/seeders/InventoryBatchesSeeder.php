<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Enums\ReceiptStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Services\Inventory\BatchCodeGenerator;
use App\Services\Inventory\DeliveryProgress;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Inventory stock: batches for each stock receipt, plus the matching
 * "receipt" ledger entries, the same as StockReceiptsForm::store() writes.
 *
 * Receipts are processed oldest first so each transaction's balance_after
 * is the item's running total at that point in time.
 */
class InventoryBatchesSeeder extends Seeder
{
    protected bool $madeExpired = false;

    protected bool $madeExpiringSoon = false;

    public function run(BatchCodeGenerator $codes): void
    {
        $items = InventoryItems::where('is_active', true)->get();
        $locations = StorageLocations::pluck('id');

        if ($items->isEmpty() || $locations->isEmpty()) {
            $this->command?->warn('No active inventory items or storage locations; skipping batches.');

            return;
        }

        // Each item keeps a roughly stable price and a home location across deliveries.
        $baseCost = $items->mapWithKeys(fn (InventoryItems $item) => [$item->id => fake()->randomFloat(2, 8, 1800)]);
        $homeLocation = $items->mapWithKeys(fn (InventoryItems $item) => [$item->id => $locations->random()]);

        // Leave a few non-perishable items without any stock so "out of stock"
        // shows up too. Perishables are always stocked so FEFO has data.
        [$perishable, $regular] = $items->partition('requires_expiry');
        $stocked = $perishable->concat(
            $regular->shuffle()->slice(0, max(1, (int) round($regular->count() * 0.8)))
        );

        $receipts = StockReceipts::with(['lines', 'parent'])->orderBy('received_date')->orderBy('id')->get();

        foreach ($receipts as $receipt) {
            $receivedAt = Carbon::parse($receipt->received_date);

            // Partial deliveries (and their follow-ups) are planned from their ordered lines.
            if ($receipt->isFollowUp() || $receipt->lines->isNotEmpty()) {
                foreach ($this->plannedQuantities($receipt) as $itemId => $qty) {
                    $this->createBatch($receipt, $items->firstWhere('id', $itemId), $qty, $receivedAt, $codes, $baseCost, $homeLocation, $locations);
                }

                continue;
            }

            foreach ($stocked->random(min($stocked->count(), fake()->numberBetween(1, 5))) as $item) {
                $this->createBatch($receipt, $item, $this->quantityFor($item), $receivedAt, $codes, $baseCost, $homeLocation, $locations);
            }
        }
    }

    /**
     * Quantity received per item for a partial root (about half of what was
     * ordered) or a follow-up (all of the balance when it completes the
     * delivery, otherwise half of it).
     *
     * @return array<int, int>
     */
    protected function plannedQuantities(StockReceipts $receipt): array
    {
        $progress = app(DeliveryProgress::class);
        $planned = [];

        if (! $receipt->isFollowUp()) {
            foreach ($receipt->lines as $line) {
                $planned[$line->inventory_item_id] = max(1, intdiv($line->ordered_qty, 2));
            }

            return $planned;
        }

        foreach ($progress->lines($receipt->parent) as $line) {
            if ($line['outstanding'] < 1) {
                continue;
            }

            $planned[$line['item']->id] = $receipt->status === ReceiptStatus::Complete
                ? $line['outstanding']
                : max(1, intdiv($line['outstanding'], 2));
        }

        return $planned;
    }

    /**
     * @param  Collection<int, float>  $baseCost
     * @param  Collection<int, int>  $homeLocation
     * @param  Collection<int, int>  $locations
     */
    protected function createBatch(StockReceipts $receipt, InventoryItems $item, int $qty, CarbonInterface $receivedAt, BatchCodeGenerator $codes, Collection $baseCost, Collection $homeLocation, Collection $locations): void
    {
        $batch = InventoryBatches::factory()->create([
            'inventory_item_id' => $item->id,
            'stock_receipt_id' => $receipt->id,
            'storage_location_id' => fake()->boolean(80) ? $homeLocation[$item->id] : $locations->random(),
            'batch_number' => $codes->generate('batch_number', $receivedAt),
            'lot_number' => $codes->generate('lot_number', $receivedAt),
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'received_at' => $receivedAt,
            'unit_cost' => round($baseCost[$item->id] * fake()->randomFloat(2, 0.9, 1.1), 2),
            ...$this->datesFor($item, $receivedAt),
        ]);

        $this->ledger($batch, $receipt, $receivedAt);
    }

    /**
     * Mostly healthy quantities, with some small deliveries so a few items
     * land at or under their reorder level.
     */
    protected function quantityFor(InventoryItems $item): int
    {
        return fake()->boolean(25)
            ? fake()->numberBetween(1, max(2, (int) floor($item->minimum_stock / 2)))
            : fake()->numberBetween($item->reorder_level, $item->reorder_level * 4);
    }

    /**
     * Expiry dates for perishable items: mostly well in the future, with some
     * already expired and some expiring within 30 days (FEFO picks those first).
     *
     * @return array{manufactured_date: ?string, expiration_date: ?string}
     */
    protected function datesFor(InventoryItems $item, CarbonInterface $receivedAt): array
    {
        if (! $item->requires_expiry) {
            return [
                'manufactured_date' => fake()->boolean(40)
                    ? $receivedAt->copy()->subDays(fake()->numberBetween(30, 365))->toDateString()
                    : null,
                'expiration_date' => null,
            ];
        }

        $roll = fake()->numberBetween(1, 100);

        // Guarantee at least one expired and one soon-to-expire batch per run.
        $canExpire = $receivedAt->lt(today()->subDays(60));

        if (! $this->madeExpired && $canExpire) {
            $roll = 1;
        } elseif (! $this->madeExpiringSoon) {
            $roll = 25;
        }

        $expiration = match (true) {
            $roll <= 20 => today()->subDays(fake()->numberBetween(1, 45)),
            $roll <= 40 => today()->addDays(fake()->numberBetween(1, 30)),
            default => today()->addDays(fake()->numberBetween(60, 720)),
        };

        // An expiry can't fall before the day the stock was received.
        if ($expiration->lt($receivedAt)) {
            $expiration = $receivedAt->copy()->addDays(fake()->numberBetween(7, 30))->startOfDay();
        }

        $this->madeExpired = $this->madeExpired || $expiration->lt(today());
        $this->madeExpiringSoon = $this->madeExpiringSoon
            || $expiration->between(today(), today()->addDays(30));

        return [
            'manufactured_date' => $receivedAt->copy()->subDays(fake()->numberBetween(15, 180))->toDateString(),
            'expiration_date' => $expiration->toDateString(),
        ];
    }

    protected function ledger(InventoryBatches $batch, StockReceipts $receipt, CarbonInterface $at): void
    {
        $transaction = new InventoryTransactions([
            'inventory_item_id' => $batch->inventory_item_id,
            'batch_id' => $batch->id,
            'user_id' => $receipt->received_by,
            'type' => InventoryTransactionType::Receipt,
            'quantity' => $batch->received_qty,
            'balance_after' => InventoryBatches::where('inventory_item_id', $batch->inventory_item_id)->sum('remaining_qty'),
            'reference_type' => StockReceipts::class,
            'reference_id' => $receipt->id,
        ]);

        $transaction->created_at = $at;
        $transaction->updated_at = $at;
        $transaction->save();
    }
}
