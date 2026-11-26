<?php

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Livewire\Inventory\AdjustmentCreateForm;
use App\Livewire\Inventory\Adjustments;
use App\Livewire\Inventory\Stocks;
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

    $receipt = StockReceipts::create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->user->id,
        'reference_number' => 'DR-001',
        'received_date' => today(),
        'delivery_date' => now(),
        'status' => 'complete_delivery',
    ]);

    $this->batch = InventoryBatches::create([
        'inventory_item_id' => InventoryItems::factory()->create()->id,
        'stock_receipt_id' => $receipt->id,
        'storage_location_id' => StorageLocations::factory()->create()->id,
        'batch_number' => 'BAT-1',
        'lot_number' => 'LOT-1',
        'received_qty' => 100,
        'remaining_qty' => 80,
        'received_at' => now(),
        'unit_cost' => 10,
    ]);
});

function adjust(InventoryBatches $batch, AdjustmentType $type, int $qty)
{
    return Livewire::test(AdjustmentCreateForm::class)
        ->set('form.type', $type->value)
        ->set('form.batch_id', $batch->id)
        ->set('form.quantity', $qty)
        ->set('form.reason', 'Test reason')
        ->set('form.approved_by', 'Mark Supervisor')
        ->call('save');
}

test('the adjustments page renders', function () {
    $this->get(route('inventory.adjustments'))->assertOk()->assertSee('Stock Adjustments');
});

test('damage records previous, change and new quantity and updates the batch', function () {
    adjust($this->batch, AdjustmentType::Damage, 5)->assertHasNoErrors();

    $adjustment = InventoryAdjustments::sole();

    expect($adjustment->type)->toBe(AdjustmentType::Damage)
        ->and($adjustment->previous_qty)->toBe(80)
        ->and($adjustment->changed_qty)->toBe(-5)
        ->and($adjustment->new_qty)->toBe(75)
        ->and($adjustment->requested_by)->toBe('Jane Clerk')
        ->and($adjustment->approved_by)->toBe('Mark Supervisor')
        ->and($adjustment->user_id)->toBe($this->user->id)
        ->and($this->batch->fresh()->remaining_qty)->toBe(75);

    $transaction = InventoryTransactions::sole();
    expect($transaction->type)->toBe(InventoryTransactionType::Adjustment)
        ->and($transaction->quantity)->toBe(-5)
        ->and($transaction->user_id)->toBe($this->user->id)
        ->and((int) $transaction->reference_id)->toBe($adjustment->id);
});

test('count correction sets the batch to the counted quantity', function () {
    adjust($this->batch, AdjustmentType::CountCorrection, 83)->assertHasNoErrors();

    expect(InventoryAdjustments::sole()->changed_qty)->toBe(3)
        ->and($this->batch->fresh()->remaining_qty)->toBe(83);
});

test('returns add stock back but not above the received quantity', function () {
    adjust($this->batch, AdjustmentType::Return, 10)->assertHasNoErrors();
    expect($this->batch->fresh()->remaining_qty)->toBe(90);

    adjust($this->batch, AdjustmentType::Return, 11)->assertHasErrors('form.quantity');
    expect($this->batch->fresh()->remaining_qty)->toBe(90);
});

test('expiry write-off defaults to the full remaining quantity', function () {
    Livewire::test(AdjustmentCreateForm::class)
        ->set('form.batch_id', $this->batch->id)
        ->set('form.type', AdjustmentType::ExpiryWriteOff->value)
        ->assertSet('form.quantity', 80);
});

test('cannot remove more than what remains', function () {
    adjust($this->batch, AdjustmentType::Loss, 81)->assertHasErrors('form.quantity');

    expect(InventoryAdjustments::count())->toBe(0)
        ->and($this->batch->fresh()->remaining_qty)->toBe(80);
});

test('reason, requester and approver are required', function () {
    Livewire::test(AdjustmentCreateForm::class)
        ->set('form.type', AdjustmentType::Loss->value)
        ->set('form.batch_id', $this->batch->id)
        ->set('form.quantity', 1)
        ->set('form.requested_by', '')
        ->call('save')
        ->assertHasErrors(['form.reason', 'form.requested_by', 'form.approved_by']);
});

test('adjustment history can never be modified or deleted', function () {
    adjust($this->batch, AdjustmentType::Damage, 5);
    $adjustment = InventoryAdjustments::sole();

    expect(fn () => $adjustment->update(['new_qty' => 999]))->toThrow(LogicException::class)
        ->and(fn () => $adjustment->delete())->toThrow(LogicException::class);

    expect(InventoryAdjustments::sole()->new_qty)->toBe(75);
});

test('history can be filtered by type', function () {
    adjust($this->batch, AdjustmentType::Damage, 1);
    adjust($this->batch, AdjustmentType::Loss, 1);

    $component = Livewire::withoutLazyLoading()->test(Adjustments::class)->set('type', AdjustmentType::Loss->value);

    expect($component->instance()->adjustments->total())->toBe(1);
});

test('each adjustment row expands to the full reason and links to its batch in inventory stock', function () {
    $reason = 'Carton crushed during unloading; 12 reams soaked by rain. Supplier notified and replacement requested under DR-001.';

    Livewire::test(AdjustmentCreateForm::class)
        ->set('form.type', AdjustmentType::Damage->value)
        ->set('form.batch_id', $this->batch->id)
        ->set('form.quantity', 5)
        ->set('form.reason', $reason)
        ->set('form.approved_by', 'Mark Supervisor')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::withoutLazyLoading()->test(Adjustments::class)
        ->assertSeeHtml('id="adjustment-details-'.InventoryAdjustments::sole()->id.'"')
        ->assertSee($reason)
        ->assertSee($this->batch->storageLocation->name)
        ->assertSee('Recorded By')
        ->assertSee('Jane Clerk')
        ->assertSee('View in Inventory Stock')
        ->assertSeeHtml(e(route('inventory.stocks', ['batch' => $this->batch->id])));
});

test('the inventory stock page filters to one batch and can clear it', function () {
    $other = $this->batch->replicate()->fill(['batch_number' => 'BAT-2']);
    $other->save();

    Livewire::withoutLazyLoading()->withQueryParams(['batch' => $this->batch->id])->test(Stocks::class)
        ->assertSee('Showing batch')
        ->assertSee('BAT-1')
        ->assertDontSee('BAT-2')
        ->call('clearBatch')
        ->assertSet('batch', null)
        ->assertSee('BAT-2');
});
