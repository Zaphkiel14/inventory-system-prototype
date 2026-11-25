<?php

use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\Stocks;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
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
        ->set('form.items.0.unit_cost', 5)
        ->set('attachments', [UploadedFile::fake()->create('dr.pdf', 50, 'application/pdf')]);
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

it('links each receipt to its stock batches on the inventory stock page', function () {
    $receipt = StockReceipts::factory()->create(['supplier_id' => $this->supplier->id, 'reference_number' => 'DR-LINK-001']);
    InventoryBatches::factory()->count(2)->create(['stock_receipt_id' => $receipt->id, 'inventory_item_id' => $this->item->id, 'storage_location_id' => $this->location->id]);

    $this->get(route('inventory.receiving'))
        ->assertOk()
        ->assertSee(route('inventory.stocks', ['receipt' => $receipt->id]), false)
        ->assertSee('View stock batches (2)');
});

it('filters the inventory stock page to one receipt and can clear it', function () {
    $mine = StockReceipts::factory()->create(['supplier_id' => $this->supplier->id, 'reference_number' => 'DR-1']);
    $other = StockReceipts::factory()->create(['supplier_id' => $this->supplier->id, 'reference_number' => 'DR-10']);

    $shown = InventoryBatches::factory()->create(['stock_receipt_id' => $mine->id, 'inventory_item_id' => $this->item->id, 'storage_location_id' => $this->location->id]);
    $hidden = InventoryBatches::factory()->create(['stock_receipt_id' => $other->id, 'inventory_item_id' => $this->item->id, 'storage_location_id' => $this->location->id]);

    $page = Livewire::withQueryParams(['receipt' => $mine->id])->test(Stocks::class)
        ->assertSet('receipt', $mine->id)
        ->assertSee('Showing batches from receipt')
        ->assertSee($shown->batch_number)
        ->assertDontSee($hidden->batch_number);

    $page->call('clearReceipt')
        ->assertSet('receipt', null)
        ->assertSee($shown->batch_number)
        ->assertSee($hidden->batch_number);

    Livewire::withQueryParams(['receipt' => $mine->id])->test(Stocks::class)
        ->call('clearFilters')
        ->assertSet('receipt', null);
});

it('logs the signed-in user as the receiver and shows them in the receiving table', function () {
    $clerk = User::factory()->create(['name' => 'Rita Storekeeper']);
    $this->actingAs($clerk);

    receivingForm()->call('save')->assertHasNoErrors();

    expect((int) StockReceipts::sole()->received_by)->toBe($clerk->id);

    $this->get(route('inventory.receiving'))
        ->assertOk()
        ->assertSee('Received By')
        ->assertSee('Rita Storekeeper');
});

it('views a receipt with its batches and attachments in a modal', function () {
    Storage::fake('public');

    $clerk = User::factory()->create(['name' => 'Rita Storekeeper']);
    $receipt = StockReceipts::factory()->create([
        'supplier_id' => $this->supplier->id,
        'received_by' => $clerk->id,
        'reference_number' => 'DR-VIEW-001',
        'purchase_order_number' => 'PO-777',
        'remarks' => 'Two cartons dented on arrival.',
    ]);
    InventoryBatches::factory()->create([
        'stock_receipt_id' => $receipt->id, 'inventory_item_id' => $this->item->id, 'storage_location_id' => $this->location->id,
        'batch_number' => 'BAT-VIEW-1', 'received_qty' => 12, 'remaining_qty' => 12, 'unit_cost' => 2.5,
    ]);

    $photo = UploadedFile::fake()->image('delivery.jpg')->store('stock-receipts', 'public');
    $invoice = UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf')->store('stock-receipts', 'public');
    $receipt->attachments()->create(['path' => $photo]);
    $receipt->attachments()->create(['path' => $invoice]);
    $receipt->attachments()->create(['path' => 'stock-receipts/gone.pdf']);

    $page = Livewire::test(Receiving::class)
        ->assertSee('View details')
        ->call('view', $receipt->id)
        ->assertSet('viewingId', $receipt->id)
        ->assertDispatched('modal-show', name: 'view-receipt')
        ->assertSee('DR-VIEW-001')
        ->assertSee('PO-777')
        ->assertSee('Rita Storekeeper')
        ->assertSee('Two cartons dented on arrival.')
        ->assertSee('BAT-VIEW-1')
        ->assertSee('30.00') // total cost: 12 x 2.50
        ->assertSee('Attachments')
        ->assertSee(basename($photo))
        ->assertSee(basename($invoice))
        ->assertSee('File is missing from storage')
        ->assertSeeHtml(Storage::disk('public')->url($photo));

    $groups = $page->instance()->viewingAttachments;

    expect($groups)->toHaveCount(1)
        ->and($groups[0]['receipt_id'])->toBe($receipt->id)
        ->and($groups[0]['is_current'])->toBeTrue();

    $files = collect($groups[0]['files'])->keyBy('name');

    expect($files[basename($photo)]['image'])->toBeTrue()
        ->and($files[basename($invoice)]['pdf'])->toBeTrue()
        ->and($files['gone.pdf']['url'])->toBeNull();
});

it('says so when a receipt has no attachments', function () {
    $receipt = StockReceipts::factory()->create(['supplier_id' => $this->supplier->id]);

    Livewire::test(Receiving::class)
        ->call('view', $receipt->id)
        ->assertSee('No files were attached to this receipt.');
});
