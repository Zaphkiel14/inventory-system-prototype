<?php

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Livewire\Monitoring\ExpirationMonitoring;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Jane Clerk']);
    $this->actingAs($this->user);

    InventoryCategories::factory()->create();

    $this->receipt = StockReceipts::factory()->create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->user->id,
    ]);
    $this->location = StorageLocations::factory()->create(['name' => 'Cold Room']);
    $this->item = InventoryItems::factory()->create(['name' => 'Alcohol 70%', 'requires_expiry' => true]);
});

function expiringBatch(int $daysFromToday, int $qty = 10, float $cost = 2, array $overrides = []): InventoryBatches
{
    return InventoryBatches::factory()->create([
        'inventory_item_id' => test()->item->id,
        'stock_receipt_id' => test()->receipt->id,
        'storage_location_id' => test()->location->id,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'unit_cost' => $cost,
        'received_at' => now()->subMonths(6),
        'expiration_date' => today()->addDays($daysFromToday),
        ...$overrides,
    ]);
}

test('the expiration monitoring page renders', function () {
    expiringBatch(-3);

    $this->get(route('monitoring.expiration-monitoring'))
        ->assertOk()
        ->assertSee('Expiration Monitoring')
        ->assertSee('Loading expiration monitoring');

    Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->assertSee('3 days ago');
});

test('only dated batches with stock left are listed', function () {
    $tracked = expiringBatch(10);
    expiringBatch(10, overrides: ['remaining_qty' => 0]);
    expiringBatch(10, overrides: ['expiration_date' => null]);

    $ids = Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)->instance()->batches->pluck('id')->all();

    expect($ids)->toBe([$tracked->id]);
});

test('summary bands count batches, units and value', function () {
    expiringBatch(-1, 4, 10);   // expired
    expiringBatch(3, 5, 2);     // 7, 30, 90
    expiringBatch(20, 6, 1);    // 30, 90
    expiringBatch(200, 7, 1);   // later only

    $summary = Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)->instance()->summary;

    expect($summary['expired'])->toBe(['batches' => 1, 'units' => 4, 'value' => 40.0])
        ->and($summary['7']['batches'])->toBe(1)
        ->and($summary['30']['batches'])->toBe(2)
        ->and($summary['90'])->toBe(['batches' => 2, 'units' => 11, 'value' => 16.0]);
});

test('status filter, search and sort narrow and order the list', function () {
    $expired = expiringBatch(-5, overrides: ['batch_number' => 'BAT-OLD']);
    $soon = expiringBatch(5, overrides: ['batch_number' => 'BAT-SOON']);
    $later = expiringBatch(120, 50, overrides: ['batch_number' => 'BAT-LATER']);

    $component = Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class);
    expect($component->instance()->batches->pluck('id')->all())->toBe([$expired->id, $soon->id, $later->id]);

    $component->set('status', 'expired');
    expect($component->instance()->batches->pluck('id')->all())->toBe([$expired->id]);

    $component->set('status', 'later');
    expect($component->instance()->batches->pluck('id')->all())->toBe([$later->id]);

    $component->set('status', '')->set('search', 'SOON');
    expect($component->instance()->batches->pluck('id')->all())->toBe([$soon->id]);

    $component->set('search', '')->set('sort', 'quantity');
    expect($component->instance()->batches->first()->id)->toBe($later->id);

    $component->call('filterStatus', '7')->assertSet('status', '7')
        ->call('filterStatus', '7')->assertSet('status', '');
});

test('the timeline puts expired value first and buckets the next six months', function () {
    expiringBatch(-2, 4, 10);
    $nextMonth = today()->startOfMonth()->addMonth()->addDays(3);
    expiringBatch((int) today()->diffInDays($nextMonth), 5, 2);

    $timeline = Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)->instance()->timeline;

    expect($timeline)->toHaveCount(7)
        ->and($timeline[0])->toMatchArray(['month' => 'Expired', 'expired' => 40.0])
        ->and($timeline[2])->toMatchArray(['month' => $nextMonth->format('M Y'), 'expiring' => 10.0]);
});

test('writing off an expired batch records an expiry write-off and zeroes the batch', function () {
    $batch = expiringBatch(-4, 12);

    Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->call('confirmWriteOff', $batch->id)
        ->assertSet('writeOffIds', [$batch->id])
        ->assertSet('requestedBy', 'Jane Clerk')
        ->set('approvedBy', 'Mark Supervisor')
        ->call('writeOff')
        ->assertHasNoErrors();

    $adjustment = InventoryAdjustments::sole();

    expect($adjustment->type)->toBe(AdjustmentType::ExpiryWriteOff)
        ->and($adjustment->previous_qty)->toBe(12)
        ->and($adjustment->changed_qty)->toBe(-12)
        ->and($adjustment->new_qty)->toBe(0)
        ->and($adjustment->approved_by)->toBe('Mark Supervisor')
        ->and($batch->fresh()->remaining_qty)->toBe(0)
        ->and(InventoryTransactions::sole()->type)->toBe(InventoryTransactionType::Adjustment);
});

test('bulk write-off handles every selected expired batch', function () {
    $a = expiringBatch(-1, 3);
    $b = expiringBatch(-10, 8);

    Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->set('selected', [$a->id, $b->id])
        ->call('confirmWriteOff')
        ->set('approvedBy', 'Mark Supervisor')
        ->call('writeOff')
        ->assertHasNoErrors()
        ->assertSet('selected', []);

    expect(InventoryAdjustments::count())->toBe(2)
        ->and($a->fresh()->remaining_qty)->toBe(0)
        ->and($b->fresh()->remaining_qty)->toBe(0);
});

test('batches that have not expired cannot be written off', function () {
    $fresh = expiringBatch(5);

    Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->call('confirmWriteOff', $fresh->id)
        ->assertSet('writeOffIds', [])
        ->set('writeOffIds', [$fresh->id])       // tampered request
        ->set('requestedBy', 'Jane Clerk')
        ->set('approvedBy', 'Mark Supervisor')
        ->call('writeOff')
        ->assertHasErrors('writeOffIds');

    expect(InventoryAdjustments::count())->toBe(0)
        ->and($fresh->fresh()->remaining_qty)->toBe(10);
});

test('an approver and reason are required', function () {
    $batch = expiringBatch(-1);

    Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->call('confirmWriteOff', $batch->id)
        ->set('reason', '')
        ->call('writeOff')
        ->assertHasErrors(['reason', 'approvedBy']);

    expect($batch->fresh()->remaining_qty)->toBe(10);
});
