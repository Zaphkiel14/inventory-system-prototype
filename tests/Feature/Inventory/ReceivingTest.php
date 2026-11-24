<?php

use App\Livewire\Inventory\ReceivingCreateForm;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    $this->supplier = Suppliers::factory()->create();
    $this->location = StorageLocations::factory()->create();
    $this->item = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true]);
});

function receivingForm(): Testable
{
    return Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', test()->supplier->id)
        ->set('form.reference_number', 'DR-TIME-001')
        ->set('form.items.0.inventory_item_id', test()->item->id)
        ->set('form.items.0.storage_location_id', test()->location->id)
        ->set('form.items.0.quantity', 10)
        ->set('form.items.0.unit_cost', 5);
}

it('defaults the received and delivery dates to the current date and time', function () {
    $this->travelTo(now()->setDate(2026, 11, 24)->setTime(14, 35));

    Livewire::test(ReceivingCreateForm::class)
        ->assertSet('form.received_date', '2026-11-24T14:35')
        ->assertSet('form.delivery_date', '2026-11-24T14:35');
});

it('requires a delivery date', function () {
    receivingForm()
        ->set('form.delivery_date', '')
        ->call('save')
        ->assertHasErrors(['form.delivery_date' => 'required']);

    expect(StockReceipts::count())->toBe(0);
});

it('stores the received and delivery dates with their times', function () {
    receivingForm()
        ->set('form.delivery_date', '2026-11-23T08:45')
        ->set('form.received_date', '2026-11-23T10:15')
        ->call('save')
        ->assertHasNoErrors();

    $receipt = StockReceipts::sole();

    expect($receipt->delivery_date->format('Y-m-d H:i'))->toBe('2026-11-23 08:45')
        ->and($receipt->received_date->format('Y-m-d H:i'))->toBe('2026-11-23 10:15');

    $this->get(route('inventory.receiving'))->assertSee('10:15 AM');
});

it('requires a received date', function () {
    receivingForm()
        ->set('form.received_date', '')
        ->call('save')
        ->assertHasErrors(['form.received_date' => 'required']);
});
