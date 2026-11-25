<?php

namespace Database\Seeders;

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryTransactions;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\InventoryAdjustmentsFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Adjustments the same way InventoryAdjustmentsForm::store() records them:
 * adjustment row, batch remaining_qty update, then an "adjustment" ledger entry.
 *
 * Every adjustment is dated after the item's last receipt, and they're applied
 * oldest first, so balance_after stays correct throughout the ledger.
 */
class InventoryAdjustmentsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $batches = InventoryBatches::with('inventoryItem')->where('remaining_qty', '>', 0)->get();

        if ($users->isEmpty() || $batches->isEmpty()) {
            $this->command?->warn('No users or stocked batches; skipping adjustments.');

            return;
        }

        $lastReceipt = InventoryBatches::query()
            ->selectRaw('inventory_item_id, max(received_at) as last_received_at')
            ->groupBy('inventory_item_id')
            ->pluck('last_received_at', 'inventory_item_id');

        $plans = $this->expiryWriteOffs($batches)
            ->concat($this->routineAdjustments($batches))
            ->map(function (array $plan) use ($lastReceipt) {
                $from = Carbon::parse($lastReceipt[$plan['batch']->inventory_item_id]);

                // A write-off happens once the batch has actually expired.
                if ($plan['type'] === AdjustmentType::ExpiryWriteOff) {
                    $from = $from->max($plan['batch']->expiration_date->copy()->addDay());
                }
                $plan['at'] = $from->lt(now())
                    ? Carbon::instance(fake()->dateTimeBetween($from, 'now'))
                    : now();

                return $plan;
            })
            ->sortBy('at')
            ->values();

        // A Return only makes sense once some of the batch is gone, so queue a
        // few after the decreases above have been applied.
        $plans = $plans->concat(
            $plans->filter(fn (array $p) => in_array($p['type'], [AdjustmentType::Damage, AdjustmentType::Loss], true))
                ->take(3)
                ->map(fn (array $p) => [
                    'batch' => $p['batch'],
                    'type' => AdjustmentType::Return,
                    'at' => Carbon::instance(fake()->dateTimeBetween($p['at'], 'now')),
                ])
        )->sortBy('at')->values();

        foreach ($plans as $plan) {
            $this->apply($plan['batch']->fresh(), $plan['type'], $plan['at'], $users->random());
        }
    }

    /**
     * Expired batches that still have stock get written off (most of them;
     * a few are left on the shelf so the expired-stock views have data).
     */
    protected function expiryWriteOffs(Collection $batches): Collection
    {
        return $batches
            ->filter(fn (InventoryBatches $b) => $b->expiration_date?->isPast())
            ->filter(fn () => fake()->boolean(70))
            ->map(fn (InventoryBatches $b) => ['batch' => $b, 'type' => AdjustmentType::ExpiryWriteOff]);
    }

    protected function routineAdjustments(Collection $batches): Collection
    {
        $usable = $batches->reject(fn (InventoryBatches $b) => $b->expiration_date?->isPast());

        return $usable
            ->random(min($usable->count(), 15))
            ->map(fn (InventoryBatches $b) => [
                'batch' => $b,
                'type' => fake()->randomElement([
                    AdjustmentType::Damage,
                    AdjustmentType::Damage,
                    AdjustmentType::Loss,
                    AdjustmentType::CountCorrection,
                    AdjustmentType::CountCorrection,
                ]),
            ]);
    }

    protected function apply(InventoryBatches $batch, AdjustmentType $type, CarbonInterface $at, User $user): void
    {
        $change = InventoryAdjustmentsFactory::change($type, $batch);
        $previous = $batch->remaining_qty;
        $new = $previous + $change;

        if ($change === 0 || $new < 0 || ($type === AdjustmentType::Return && $new > $batch->received_qty)) {
            return;
        }

        $adjustment = new InventoryAdjustments([
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'type' => $type,
            'requested_by' => $user->name,
            'approved_by' => fake()->name(),
            'previous_qty' => $previous,
            'changed_qty' => $change,
            'new_qty' => $new,
            'reason' => fake()->randomElement(InventoryAdjustmentsFactory::REASONS[$type->value]),
        ]);
        $adjustment->created_at = $at;
        $adjustment->updated_at = $at;
        $adjustment->save();

        $batch->update(['remaining_qty' => $new]);

        $transaction = new InventoryTransactions([
            'inventory_item_id' => $batch->inventory_item_id,
            'batch_id' => $batch->id,
            'user_id' => $user->id,
            'type' => InventoryTransactionType::Adjustment,
            'quantity' => $change,
            'balance_after' => InventoryBatches::where('inventory_item_id', $batch->inventory_item_id)->sum('remaining_qty'),
            'reference_type' => InventoryAdjustments::class,
            'reference_id' => $adjustment->id,
        ]);
        $transaction->created_at = $at;
        $transaction->updated_at = $at;
        $transaction->save();
    }
}
