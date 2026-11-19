<?php

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Livewire\Monitoring\Reports;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    InventoryCategories::factory()->create();

    $this->supplier = Suppliers::factory()->create(['name' => 'Acme Supply']);
    $this->location = StorageLocations::factory()->create(['name' => 'Main Storage Room']);

    $this->item = InventoryItems::factory()->create([
        'name' => 'A4 Copy Paper',
        'minimum_stock' => 5,
        'reorder_level' => 20,
        'is_active' => true,
    ]);
});

function receive(InventoryItems $item, int $qty, float $cost, CarbonInterface $at, array $overrides = []): InventoryBatches
{
    $receipt = StockReceipts::factory()->create([
        'supplier_id' => test()->supplier->id,
        'received_by' => test()->user->id,
        'received_date' => $at,
    ]);

    $batch = InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => $receipt->id,
        'storage_location_id' => test()->location->id,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'received_at' => $at,
        'unit_cost' => $cost,
        'expiration_date' => null,
        ...$overrides,
    ]);

    ledger($batch, InventoryTransactionType::Receipt, $qty, $at);

    return $batch;
}

function ledger(InventoryBatches $batch, InventoryTransactionType $type, int $qty, CarbonInterface $at): void
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
}

test('the reports page renders', function () {
    receive($this->item, 50, 10, now()->subDays(3));

    $this->get(route('monitoring.reports'))
        ->assertOk()
        ->assertSee('Stock movement')
        ->assertSee('Items needing reorder');
});

test('the reports page renders with no data', function () {
    $this->get(route('monitoring.reports'))
        ->assertOk()
        ->assertSee('No stock movement in this period.');
});

test('summary totals only count the selected period and compare to the previous one', function () {
    receive($this->item, 40, 10, now()->subDays(5));    // in the last 30 days
    receive($this->item, 20, 10, now()->subDays(40));   // previous 30 days
    receive($this->item, 99, 10, now()->subDays(200));  // outside both

    $batch = InventoryBatches::first();
    $batch->update(['remaining_qty' => 30]);
    ledger($batch, InventoryTransactionType::Issuance, -10, now()->subDays(2));

    $summary = Livewire::test(Reports::class)->set('range', '30')->instance()->summary;

    expect($summary['received'])->toBe(40)
        ->and($summary['received_delta'])->toBe(100.0)
        ->and($summary['issued'])->toBe(10)
        ->and($summary['issued_delta'])->toBeNull();
});

test('stock value excludes expired batches', function () {
    receive($this->item, 10, 5, now()->subDays(3));
    receive($this->item, 4, 100, now()->subDays(60), ['expiration_date' => today()->subDay()]);

    $summary = Livewire::test(Reports::class)->instance()->summary;

    expect($summary['stock_value'])->toBe(50.0)
        ->and($summary['stock_units'])->toBe(10)
        ->and($summary['expired_value'])->toBe(400.0);
});

test('stock health and the reorder list classify items by usable stock', function () {
    $critical = $this->item;                                              // 3 <= minimum 5
    receive($critical, 3, 1, now()->subDays(3));

    $reorder = InventoryItems::factory()->create(['minimum_stock' => 5, 'reorder_level' => 20]);
    receive($reorder, 15, 1, now()->subDays(3));                          // 15 <= reorder 20

    $healthy = InventoryItems::factory()->create(['minimum_stock' => 5, 'reorder_level' => 20]);
    receive($healthy, 50, 1, now()->subDays(3));

    $out = InventoryItems::factory()->create(['minimum_stock' => 5, 'reorder_level' => 20]);  // never received

    $inactive = InventoryItems::factory()->create(['is_active' => false]);

    $component = Livewire::test(Reports::class)->instance();

    expect($component->stockHealth)->toBe(['out' => 1, 'critical' => 1, 'reorder' => 1, 'healthy' => 1])
        ->and($component->reorderItems->pluck('id')->all())->toBe([$out->id, $critical->id, $reorder->id])
        ->and($component->reorderItems->firstWhere('id', $reorder->id)->shortfall)->toBe(5)
        ->and($component->reorderItems->pluck('id'))->not->toContain($inactive->id);
});

test('movement is bucketed by day for short ranges and by month for long ones', function () {
    receive($this->item, 12, 1, now()->subDays(2));

    $component = Livewire::test(Reports::class)->set('range', '30');
    $movement = $component->instance()->movement;

    expect($component->instance()->bucket)->toBe('day')
        ->and($movement)->toHaveCount(30)
        ->and(collect($movement)->firstWhere('date', today()->subDays(2)->toDateString())['received'])->toBe(12);

    $component->set('range', '365');
    expect($component->instance()->bucket)->toBe('month')
        ->and(collect($component->instance()->movement)->sum('received'))->toBe(12);
});

test('adjustments by type count records in the period', function () {
    $batch = receive($this->item, 50, 1, now()->subDays(10));

    foreach ([[AdjustmentType::Damage, -3], [AdjustmentType::Damage, -2], [AdjustmentType::Return, 1]] as [$type, $change]) {
        InventoryAdjustments::create([
            'batch_id' => $batch->id,
            'user_id' => $this->user->id,
            'type' => $type,
            'requested_by' => 'A',
            'approved_by' => 'B',
            'previous_qty' => 50,
            'changed_qty' => $change,
            'new_qty' => 50 + $change,
            'reason' => 'Test',
        ]);
    }

    $rows = Livewire::test(Reports::class)->instance()->adjustmentsByType->keyBy(fn ($r) => $r['type']->value);

    expect($rows)->toHaveCount(2)
        ->and($rows['damage']['count'])->toBe(2)
        ->and($rows['damage']['units'])->toBe(-5)
        ->and($rows['return']['units'])->toBe(1);
});

test('custom ranges use the given dates and an unknown range falls back to the default', function () {
    receive($this->item, 7, 1, Carbon::parse('2026-03-10'));

    $component = Livewire::test(Reports::class)
        ->set('range', 'custom')
        ->set('from', '2026-03-01')
        ->set('to', '2026-03-31');

    [$start, $end] = $component->instance()->period;

    expect($start->toDateString())->toBe('2026-03-01')
        ->and($end->toDateString())->toBe('2026-03-31')
        ->and($component->instance()->summary['received'])->toBe(7)
        ->and($component->instance()->receivingBySupplier->first())->toMatchArray(['supplier' => 'Acme Supply', 'units' => 7]);

    Livewire::withQueryParams(['range' => 'bogus'])->test(Reports::class)->assertSet('range', '90');
});
