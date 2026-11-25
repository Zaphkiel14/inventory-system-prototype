<?php

use App\Enums\ReceiptStatus;
use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Monitoring\Reports;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Services\Inventory\TransactionReferences;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->supplier = Suppliers::factory()->create(['name' => 'Acme Donors']);
    $this->location = StorageLocations::factory()->create();
    $this->item = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true]);
});

function donationForm(bool $donation = true, ?Suppliers $supplier = null, array $line = []): Testable
{
    static $n = 0;

    return Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', ($supplier ?? test()->supplier)->id)
        ->set('form.reference_number', 'DR-DON-'.++$n)
        ->set('form.is_donation', $donation)
        ->set('form.items.0.inventory_item_id', test()->item->id)
        ->set('form.items.0.storage_location_id', test()->location->id)
        ->set('form.items.0.quantity', 10)
        ->set('form.items.0.unit_cost', 5)
        ->set('attachments', [UploadedFile::fake()->create('deed.pdf', 50, 'application/pdf')]);
}

describe('recording donations', function () {
    it('stores the donation tag, and the same supplier can also record a purchase', function () {
        donationForm(true)->call('save')->assertHasNoErrors();
        donationForm(false)->call('save')->assertHasNoErrors();

        $receipts = StockReceipts::query()->orderBy('id')->get();

        expect($receipts)->toHaveCount(2)
            ->and($receipts[0]->is_donation)->toBeTrue()
            ->and($receipts[1]->is_donation)->toBeFalse()
            ->and($receipts->pluck('supplier_id')->unique()->all())->toBe([$this->supplier->id])
            ->and(StockReceipts::query()->donations()->count())->toBe(1);
    });

    it('defaults to a purchase', function () {
        Livewire::test(ReceivingCreateForm::class)->assertSet('form.is_donation', false);
    });

    it('rejects an inactive supplier even for a donation', function () {
        $inactive = Suppliers::factory()->inactive()->create();

        donationForm(true, $inactive)->call('save')->assertHasErrors(['form.supplier_id']);

        expect(StockReceipts::count())->toBe(0);
    });

    it('still requires a unit cost and stores a zero cost', function () {
        donationForm()->set('form.items.0.unit_cost', '')->call('save')->assertHasErrors(['form.items.0.unit_cost']);

        donationForm()->set('form.items.0.unit_cost', 0)->call('save')->assertHasNoErrors();

        $batch = InventoryBatches::sole();
        expect((float) $batch->unit_cost)->toBe(0.0)
            ->and($batch->stockReceipt->is_donation)->toBeTrue();
    });

    it('moves stock and writes the ledger exactly like a purchase', function () {
        donationForm()->call('save')->assertHasNoErrors();

        $tx = InventoryTransactions::sole();
        expect($tx->quantity)->toBe(10)
            ->and($tx->balance_after)->toBe(10)
            ->and((int) InventoryBatches::sole()->remaining_qty)->toBe(10);
    });

    it('requires an attachment for a donation too', function () {
        donationForm()->set('attachments', [])->call('save')->assertHasErrors(['attachments']);
    });

    it('lets a follow-up inherit the donation tag even if the form tries to clear it', function () {
        donationForm()
            ->set('form.status', ReceiptStatus::Partial->value)
            ->set('form.items.0.ordered_qty', 30)
            ->call('save')
            ->assertHasNoErrors();

        $root = StockReceipts::sole();
        expect($root->is_donation)->toBeTrue()
            ->and($root->status)->toBe(ReceiptStatus::Partial);

        Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->assertSet('form.is_donation', true)
            ->set('form.reference_number', 'DR-DON-FU')
            ->set('form.is_donation', false)
            ->set('form.items.0.unit_cost', 5)
            ->call('save')
            ->assertHasNoErrors();

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        expect($child->is_donation)->toBeTrue()
            ->and($child->status)->toBe(ReceiptStatus::Complete)
            ->and($root->fresh()->status)->toBe(ReceiptStatus::Complete);
    });
});

describe('receiving list filter', function () {
    beforeEach(function () {
        $this->donation = StockReceipts::factory()->donation()->create(['supplier_id' => $this->supplier->id]);
        $this->purchase = StockReceipts::factory()->create(['supplier_id' => $this->supplier->id, 'is_donation' => false]);
    });

    it('shows only donations', function () {
        $ids = Livewire::test(Receiving::class)->set('type', 'donation')->instance()->receipts->pluck('id')->all();

        expect($ids)->toBe([$this->donation->id]);
    });

    it('shows only purchases', function () {
        $ids = Livewire::test(Receiving::class)->set('type', 'purchase')->instance()->receipts->pluck('id')->all();

        expect($ids)->toBe([$this->purchase->id]);
    });

    it('shows everything by default and resets an unknown value', function () {
        $page = Livewire::test(Receiving::class)->assertSet('type', '');

        expect($page->instance()->receipts->pluck('id')->sort()->values()->all())
            ->toBe(collect([$this->donation->id, $this->purchase->id])->sort()->values()->all());

        $page->set('type', 'donation')->set('type', 'bogus')->assertSet('type', '');
    });
});

describe('where donations show up', function () {
    it('prefixes the ledger reference detail with Donation', function () {
        donationForm(true)->call('save')->assertHasNoErrors();
        donationForm(false)->call('save')->assertHasNoErrors();

        $donation = StockReceipts::query()->where('is_donation', true)->sole();
        $purchase = StockReceipts::query()->where('is_donation', false)->sole();

        $resolved = app(TransactionReferences::class)->resolve(InventoryTransactions::all());

        expect($resolved[StockReceipts::class.':'.$donation->id]['detail'])->toStartWith('Donation')->toContain('Acme Donors')
            ->and($resolved[StockReceipts::class.':'.$purchase->id]['detail'])->not->toContain('Donation');
    });

    it('splits received value between donated and purchased in the supplier report', function () {
        $day = now()->subDays(2);

        $donation = StockReceipts::factory()->donation()->receivedOn($day)->create(['supplier_id' => $this->supplier->id]);
        $purchase = StockReceipts::factory()->receivedOn($day)->create(['supplier_id' => $this->supplier->id, 'is_donation' => false]);

        foreach ([[$donation, 10, 3.0], [$purchase, 5, 4.0]] as [$receipt, $qty, $cost]) {
            InventoryBatches::factory()->create([
                'stock_receipt_id' => $receipt->id,
                'inventory_item_id' => $this->item->id,
                'storage_location_id' => $this->location->id,
                'received_qty' => $qty,
                'remaining_qty' => $qty,
                'unit_cost' => $cost,
            ]);
        }

        $row = Livewire::test(Reports::class)->instance()->receivingBySupplier->firstWhere('supplier', 'Acme Donors');

        expect($row['value'])->toBe(50.0)
            ->and($row['donated_value'])->toBe(30.0)
            ->and($row['purchased_value'])->toBe(20.0)
            ->and($row['donated_value'] + $row['purchased_value'])->toBe($row['value'])
            ->and($row['deliveries'])->toBe(2)
            ->and($row['units'])->toBe(15);
    });

    it('badges donations in the receiving list', function () {
        StockReceipts::factory()->donation()->create(['supplier_id' => $this->supplier->id, 'reference_number' => 'DR-BADGE-1']);

        Livewire::test(Receiving::class)->assertSee('DR-BADGE-1')->assertSee('Donation');
    });

    it('marks a donated batch on the inventory stock page', function () {
        $receipt = StockReceipts::factory()->donation()->create(['supplier_id' => $this->supplier->id]);
        InventoryBatches::factory()->create([
            'stock_receipt_id' => $receipt->id,
            'inventory_item_id' => $this->item->id,
            'storage_location_id' => $this->location->id,
        ]);

        Livewire::test(Stocks::class)->assertSee('Donated');
    });
});
