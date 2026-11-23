<?php

use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Livewire\Dashboard;
use App\Livewire\Monitoring\StockMonitoring;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/* -------------------------------------------------------------------------- */
/* Fixtures (prefixed `dash` so they don't clash with other test files' helpers) */
/* -------------------------------------------------------------------------- */

function dashSetup(): void
{
    test()->user = User::factory()->create(['name' => 'Dana Storekeeper']);
    test()->actingAs(test()->user);

    InventoryCategories::factory()->create(['is_active' => true]);

    test()->receipt = StockReceipts::factory()->create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => test()->user->id,
    ]);
    test()->location = StorageLocations::factory()->create();
}

function dashItem(array $attributes = []): InventoryItems
{
    return InventoryItems::factory()->create([
        'minimum_stock' => 5,
        'reorder_level' => 20,
        'is_active' => true,
        'requires_expiry' => false,
        ...$attributes,
    ]);
}

function dashBatch(InventoryItems $item, int $qty, array $overrides = []): InventoryBatches
{
    return InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => test()->receipt->id,
        'storage_location_id' => test()->location->id,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'unit_cost' => 2,
        'received_at' => now()->subDays(30),
        'expiration_date' => null,
        ...$overrides,
    ]);
}

function dashLedger(InventoryBatches $batch, InventoryTransactionType $type, int $qty, CarbonInterface $at): InventoryTransactions
{
    $t = new InventoryTransactions([
        'inventory_item_id' => $batch->inventory_item_id,
        'batch_id' => $batch->id,
        'user_id' => test()->user->id,
        'type' => $type,
        'quantity' => $qty,
        'balance_after' => 0,
        'reference_type' => 'test',
        'reference_id' => 1,
    ]);
    $t->created_at = $at;
    $t->save();

    return $t;
}

function dashRequest(RequisitionStatus $status, ?CarbonInterface $submittedAt = null): Requisitions
{
    return Requisitions::create([
        'request_number' => 'REQ-'.fake()->unique()->numerify('####'),
        'user_id' => test()->user->id,
        'status' => $status,
        'purpose' => 'Test',
        'submitted_at' => $submittedAt ?? now(),
    ]);
}

function dashboard(): Dashboard
{
    return Livewire::test(Dashboard::class)->instance();
}

/* -------------------------------------------------------------------------- */
/* Access */
/* -------------------------------------------------------------------------- */

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['name' => 'Dana Storekeeper']);
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Welcome back, Dana Storekeeper')
        ->assertSee('Requisition queue')
        ->assertSee('Recent activity');
});

/* -------------------------------------------------------------------------- */
/* Metrics */
/* -------------------------------------------------------------------------- */

describe('metrics', function () {
    beforeEach(fn () => dashSetup());

    test('an empty database shows zeros and empty states', function () {
        $dashboard = dashboard();

        expect($dashboard->pendingApprovals)->toBe(0)
            ->and($dashboard->readyToRelease)->toBe(0)
            ->and($dashboard->stockHealth)->toBe(['out' => 0, 'critical' => 0, 'reorder' => 0, 'healthy' => 0])
            ->and($dashboard->expiry['expiring']['batches'])->toBe(0)
            ->and($dashboard->expiry['expired']['batches'])->toBe(0)
            ->and($dashboard->queue)->toBeEmpty()
            ->and($dashboard->recentActivity)->toBeEmpty();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No requests waiting. All caught up.')
            ->assertSee('No active items yet.')
            ->assertSee('No stock movements recorded yet.');
    });

    test('stock health matches stock monitoring and handles edge cases', function () {
        dashItem();                                               // no batches: out
        dashBatch(dashItem(), 3);                                 // critical
        dashBatch(dashItem(), 15);                                // reorder
        dashBatch(dashItem(), 50);                                // healthy
        dashBatch(dashItem(), 40, ['expiration_date' => today()->subDay()]); // only expired stock: out
        dashBatch(dashItem(['is_active' => false]), 50);          // inactive: excluded

        $health = dashboard()->stockHealth;

        expect($health)->toBe(['out' => 2, 'critical' => 1, 'reorder' => 1, 'healthy' => 1])
            ->and($health)->toBe(Livewire::test(StockMonitoring::class)->instance()->counts);
    });

    test('expired batches are kept out of usable stock and counted separately', function () {
        $item = dashItem();
        dashBatch($item, 10);
        dashBatch($item, 7, ['expiration_date' => today()->subDays(3)]);

        $dashboard = dashboard();

        expect($dashboard->onHand)->toBe(['units' => 10, 'value' => 20.0])
            ->and($dashboard->expiry['expired'])->toBe(['batches' => 1, 'units' => 7, 'value' => 14.0])
            ->and($dashboard->onHand['units'])->toBe((int) InventoryBatches::query()->usable()->sum('remaining_qty'));
    });

    test('expiring soon includes the next 30 days only, soonest first', function () {
        $item = dashItem(['requires_expiry' => true]);
        $later = dashBatch($item, 5, ['expiration_date' => today()->addDays(20)]);
        $today = dashBatch($item, 5, ['expiration_date' => today()]);
        $edge = dashBatch($item, 5, ['expiration_date' => today()->addDays(30)]);
        dashBatch($item, 5, ['expiration_date' => today()->addDays(31)]);  // too far out
        dashBatch($item, 5, ['expiration_date' => today()->subDay()]);     // already expired
        dashBatch($item, 0, ['expiration_date' => today()->addDays(5)]);   // depleted

        $dashboard = dashboard();

        expect($dashboard->expiringSoon->pluck('id')->all())->toBe([$today->id, $later->id, $edge->id])
            ->and($dashboard->expiry['expiring']['batches'])->toBe(3);
    });

    test('approval and release counts use the right statuses', function () {
        dashRequest(RequisitionStatus::Pending);
        dashRequest(RequisitionStatus::Pending);
        dashRequest(RequisitionStatus::Approved);
        dashRequest(RequisitionStatus::PartiallyIssued);
        dashRequest(RequisitionStatus::Issued);
        dashRequest(RequisitionStatus::Rejected);
        dashRequest(RequisitionStatus::Cancelled);

        $dashboard = dashboard();

        expect($dashboard->pendingApprovals)->toBe(2)
            ->and($dashboard->readyToRelease)->toBe(2)
            ->and($dashboard->queueTotal)->toBe(4);
    });

    test('the queue lists the oldest waiting requests first, capped', function () {
        $newest = dashRequest(RequisitionStatus::Pending, now()->subHour());
        $oldest = dashRequest(RequisitionStatus::Approved, now()->subDays(5));
        dashRequest(RequisitionStatus::Issued, now()->subDays(10)); // done: not in queue

        foreach (range(1, Dashboard::LIST_LIMIT) as $i) {
            dashRequest(RequisitionStatus::Pending, now()->subDays(2)->addMinutes($i));
        }

        $queue = dashboard()->queue;

        expect($queue)->toHaveCount(Dashboard::LIST_LIMIT)
            ->and($queue->first()->id)->toBe($oldest->id)
            ->and($queue->pluck('id'))->not->toContain($newest->id);
    });

    test('the movement chart covers 30 days and drops older entries', function () {
        $this->travelTo(now()->setDate(2026, 11, 24)->setTime(23, 59, 59));

        $batch = dashBatch(dashItem(), 100);
        dashLedger($batch, InventoryTransactionType::Receipt, 40, today()->subDays(29)->startOfDay());  // first day in range
        dashLedger($batch, InventoryTransactionType::Receipt, 99, today()->subDays(30)->endOfDay());    // just outside
        dashLedger($batch, InventoryTransactionType::Issuance, -6, now());
        dashLedger($batch, InventoryTransactionType::Adjustment, -2, now());

        $movement = dashboard()->movement;

        expect($movement)->toHaveCount(Dashboard::MOVEMENT_DAYS)
            ->and($movement[0])->toBe(['date' => '2026-10-26', 'received' => 40, 'issued' => 0, 'written_down' => 0])
            ->and(end($movement))->toBe(['date' => '2026-11-24', 'received' => 0, 'issued' => 6, 'written_down' => 2])
            ->and(array_sum(array_column($movement, 'received')))->toBe(40);
    });

    test('recent activity shows the newest ledger rows first', function () {
        $batch = dashBatch(dashItem(['name' => 'Gauze Pads']), 100);

        foreach (range(1, Dashboard::ACTIVITY_LIMIT + 2) as $i) {
            $last = dashLedger($batch, InventoryTransactionType::Receipt, $i, now()->subMinutes(100 - $i));
        }

        $activity = dashboard()->recentActivity;

        expect($activity)->toHaveCount(Dashboard::ACTIVITY_LIMIT)
            ->and($activity->first()->id)->toBe($last->id);

        $this->get(route('dashboard'))->assertSee('Gauze Pads');
    });

    test('the page renders populated widgets', function () {
        $item = dashItem(['name' => 'Saline Solution', 'requires_expiry' => true]);
        dashBatch($item, 3, ['expiration_date' => today()->addDays(4), 'batch_number' => 'BATCH-EXP-1']);
        $request = dashRequest(RequisitionStatus::Pending);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Saline Solution')
            ->assertSee('BATCH-EXP-1')
            ->assertSee($request->request_number);
    });

    test('query count does not grow with the number of items', function () {
        $seed = function (int $n) {
            foreach (range(1, $n) as $i) {
                $item = dashItem();
                $batch = dashBatch($item, $i % 3 === 0 ? 2 : 30, ['expiration_date' => today()->addDays($i % 40)]);
                dashLedger($batch, InventoryTransactionType::Receipt, 30, now()->subDays($i % 20));
                dashRequest(RequisitionStatus::Pending)->items()->create([
                    'inventory_item_id' => $item->id,
                    'requested_qty' => 1,
                    'approved_qty' => 0,
                    'released_qty' => 0,
                    'outstanding_qty' => 0,
                ]);
            }
        };

        $count = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('dashboard'))->assertOk();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $seed(5);
        $small = $count();

        $seed(45);
        $large = $count();

        expect($large)->toBe($small);
    });
});
