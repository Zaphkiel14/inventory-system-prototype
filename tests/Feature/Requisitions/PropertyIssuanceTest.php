<?php

use App\Enums\InventoryTransactionType;
use App\Enums\PropertyClass;
use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\IssuedPropertyUnits;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Models\User;
use Carbon\CarbonInterface;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Sam Custodian']));
    $this->travelTo('2026-11-27 09:00:00');

    $this->fan = InventoryItems::factory()->property(5)->create(['name' => 'Electric Fan 16"', 'requires_expiry' => false, 'is_active' => true]);
});

function propertyBatch(InventoryItems $item, string $number, int $qty, string $unitCost, CarbonInterface $receivedAt, ?CarbonInterface $expires = null): InventoryBatches
{
    return InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'batch_number' => $number,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'unit_cost' => $unitCost,
        'received_at' => $receivedAt,
        'expiration_date' => $expires,
    ]);
}

function approvedFor(InventoryItems $item, int $qty): Requisitions
{
    return Requisitions::factory()->approved()
        ->withLine($item, $qty)
        ->create(['requester_name' => 'Jane Requester'])
        ->load('items');
}

function openRelease(Requisitions $requisition): Testable
{
    return Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
}

/**
 * The item's issuance ledger, oldest first, as [batch id, quantity, balance after].
 *
 * @return list<array{0: int, 1: int, 2: int}>
 */
function propertyLedger(InventoryItems $item): array
{
    return InventoryTransactions::query()
        ->where('inventory_item_id', $item->id)
        ->where('type', InventoryTransactionType::Issuance)
        ->orderBy('id')
        ->get()
        ->map(fn (InventoryTransactions $row) => [(int) $row->batch_id, $row->quantity, $row->balance_after])
        ->all();
}

/**
 * Every issued unit, oldest first, as [batch drawn, unit cost, class, brand, model, serial].
 *
 * @return list<array{0: int, 1: string, 2: PropertyClass, 3: ?string, 4: ?string, 5: ?string}>
 */
function issuedUnits(): array
{
    return IssuedPropertyUnits::query()
        ->with('stockIssuanceBatch')
        ->orderBy('id')
        ->get()
        ->map(fn (IssuedPropertyUnits $unit) => [
            (int) $unit->stockIssuanceBatch->batch_id,
            $unit->unit_cost,
            $unit->property_class,
            $unit->brand,
            $unit->model,
            $unit->serial_number,
        ])
        ->all();
}

it('releases one property unit row per unit with the cost and class of the batch drawn', function () {
    $cheap = propertyBatch($this->fan, 'FAN-OLD', 2, '1196.11', now()->subDays(5));
    $dear = propertyBatch($this->fan, 'FAN-NEW', 5, '6000.00', now()->subDay());
    $requisition = approvedFor($this->fan, 3);
    $line = $requisition->items->sole();

    openRelease($requisition)
        ->assertSet("form.units.{$line->id}.0.property_class", 'splv')
        ->assertSet("form.units.{$line->id}.2.property_class", 'sphv')
        ->set("form.units.{$line->id}.0.brand", ' Asahi ')
        ->set("form.units.{$line->id}.0.model", 'EF-1600')
        ->set("form.units.{$line->id}.0.serial_number", 'SN-0001')
        ->set("form.units.{$line->id}.1.serial_number", 'SN-0002')
        ->set("form.units.{$line->id}.2.brand", 'Hanabishi')
        ->set("form.units.{$line->id}.2.serial_number", 'SN-0003')
        ->set("form.issue_remarks.{$line->id}", ' For the ER nurses station ')
        ->call('release')
        ->assertHasNoErrors()
        ->assertDispatched('stock-issued', pendingPropertyUnits: 3);

    expect(issuedUnits())->toBe([
        [$cheap->id, '1196.11', PropertyClass::Splv, 'Asahi', 'EF-1600', 'SN-0001'],
        [$cheap->id, '1196.11', PropertyClass::Splv, null, null, 'SN-0002'],
        [$dear->id, '6000.00', PropertyClass::Sphv, 'Hanabishi', null, 'SN-0003'],
    ]);

    expect(propertyLedger($this->fan))->toBe([
        [$cheap->id, -2, 5],
        [$dear->id, -1, 4],
    ]);

    expect($line->fresh())
        ->released_qty->toBe(3)
        ->outstanding_qty->toBe(0)
        ->issue_remarks->toBe('For the ER nurses station')
        ->and($requisition->fresh()->status)->toBe(RequisitionStatus::Issued);
});

it('never plans or issues property units from an expired batch', function () {
    $this->fan->update(['requires_expiry' => true]);
    $expired = propertyBatch($this->fan, 'FAN-EXP', 10, '1196.11', now()->subDays(30), today()->subDay());
    $soon = propertyBatch($this->fan, 'FAN-SOON', 1, '6000.00', now()->subDays(2), today()->addMonth());
    $late = propertyBatch($this->fan, 'FAN-LATE', 5, '78000.00', now()->subDays(20), today()->addYear());
    $requisition = approvedFor($this->fan, 2);
    $line = $requisition->items->sole();

    openRelease($requisition)
        ->assertSet("form.units.{$line->id}.0.batch_id", $soon->id)
        ->assertSet("form.units.{$line->id}.1.batch_id", $late->id)
        ->call('release')
        ->assertHasNoErrors();

    expect(array_map(fn (array $unit) => [$unit[0], $unit[2]], issuedUnits()))->toBe([
        [$soon->id, PropertyClass::Sphv],
        [$late->id, PropertyClass::Ppe],
    ])->and($expired->fresh()->remaining_qty)->toBe(10)
        ->and(propertyLedger($this->fan))->toBe([
            [$soon->id, -1, 15],
            [$late->id, -1, 14],
        ]);
});

it('releases items that are not property without unit rows', function () {
    $paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_property' => false, 'requires_expiry' => false, 'is_active' => true]);
    $batch = propertyBatch($paper, 'P-1', 10, '250.00', now()->subDay());
    $requisition = approvedFor($paper, 4);

    openRelease($requisition)
        ->assertSet('form.units', [])
        ->call('release')
        ->assertHasNoErrors()
        ->assertDispatched('stock-issued', pendingPropertyUnits: 0);

    expect(IssuedPropertyUnits::count())->toBe(0)
        ->and(propertyLedger($paper))->toBe([[$batch->id, -4, 6]]);
});

it('rejects a serial number entered twice in one release, ignoring case', function () {
    propertyBatch($this->fan, 'FAN-1', 5, '1196.11', now()->subDay());
    $requisition = approvedFor($this->fan, 2);
    $line = $requisition->items->sole();

    openRelease($requisition)
        ->set("form.units.{$line->id}.0.serial_number", 'SN-0001')
        ->set("form.units.{$line->id}.1.serial_number", 'sn-0001')
        ->call('release')
        ->assertHasErrors(["form.units.{$line->id}.1.serial_number" => 'Serial number sn-0001 is entered more than once.']);

    expect(StockIssuances::count())->toBe(0)
        ->and(IssuedPropertyUnits::count())->toBe(0)
        ->and(InventoryTransactions::count())->toBe(0);
});

it('rejects a serial number already issued for the same item, ignoring case', function (string $entered) {
    IssuedPropertyUnits::factory()->create(['inventory_item_id' => $this->fan->id, 'serial_number' => 'SN-0001']);
    propertyBatch($this->fan, 'FAN-1', 5, '1196.11', now()->subDay());
    $requisition = approvedFor($this->fan, 1);
    $line = $requisition->items->sole();
    $before = [StockIssuances::count(), IssuedPropertyUnits::count(), InventoryTransactions::count()];

    openRelease($requisition)
        ->set("form.units.{$line->id}.0.serial_number", $entered)
        ->call('release')
        ->assertHasErrors(["form.units.{$line->id}.0.serial_number" => "Serial number {$entered} was already issued for this item."]);

    expect([StockIssuances::count(), IssuedPropertyUnits::count(), InventoryTransactions::count()])->toBe($before);
})->with([
    'same case' => ['SN-0001'],
    'different case' => ['sn-0001'],
]);

it('accepts a serial number already issued for a different item', function () {
    IssuedPropertyUnits::factory()->create(['serial_number' => 'SN-0001']);
    propertyBatch($this->fan, 'FAN-1', 5, '1196.11', now()->subDay());
    $requisition = approvedFor($this->fan, 1);
    $line = $requisition->items->sole();

    openRelease($requisition)
        ->set("form.units.{$line->id}.0.serial_number", 'SN-0001')
        ->call('release')
        ->assertHasNoErrors();

    expect(IssuedPropertyUnits::where('inventory_item_id', $this->fan->id)->sole()->serial_number)->toBe('SN-0001');
});

it('releases nothing when another issuance takes the planned batch first, then re-plans the units', function () {
    $cheap = propertyBatch($this->fan, 'FAN-OLD', 2, '1196.11', now()->subDays(5));
    $dear = propertyBatch($this->fan, 'FAN-NEW', 5, '6000.00', now()->subDay());
    $requisition = approvedFor($this->fan, 2);
    $line = $requisition->items->sole();

    $page = openRelease($requisition)
        ->assertSet("form.units.{$line->id}.0.batch_id", $cheap->id)
        ->assertSet("form.units.{$line->id}.1.batch_id", $cheap->id);

    // Another custodian releases both FAN-OLD units in the meantime.
    openRelease(approvedFor($this->fan, 2))->call('release')->assertHasNoErrors();
    $before = [StockIssuances::count(), IssuedPropertyUnits::count(), InventoryTransactions::count()];

    $page->call('release')
        ->assertHasErrors(["form.units.{$line->id}" => 'Stock changed since this was opened, so the units no longer match the batches. Review the units and release again.'])
        ->assertNotDispatched('stock-issued')
        ->assertSet("form.units.{$line->id}.0.batch_id", $dear->id);

    expect([StockIssuances::count(), IssuedPropertyUnits::count(), InventoryTransactions::count()])->toBe($before)
        ->and($dear->fresh()->remaining_qty)->toBe(5)
        ->and($line->fresh()->outstanding_qty)->toBe(2)
        ->and($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);

    $page->call('release')->assertHasNoErrors();

    expect($dear->fresh()->remaining_qty)->toBe(3);
});

it('drafts the ICS and PAR for a release and opens the list it belongs on', function (array $batches, string $route, string $message) {
    foreach ($batches as $i => [$cost, $qty]) {
        propertyBatch($this->fan, "FAN-{$i}", $qty, $cost, now()->subDays(10 - $i));
    }
    $requisition = approvedFor($this->fan, array_sum(array_column($batches, 1)));
    openRelease($requisition)->call('release')->assertHasNoErrors();
    $issuance = StockIssuances::sole();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('draftPropertyDocuments', $issuance->id)
        ->assertRedirect(route($route, ['issuance' => $issuance->id]));

    expect(session('property-documents-drafted'))->toBe($message)
        ->and($issuance->pendingPropertyUnitsCount())->toBe(0);
})->with([
    'SPLV and PPE units go to the ICS list first' => [[['1196.11', 2], ['78000.00', 1]], 'property-management.ics', '1 ICS and 1 PAR drafts ready to complete.'],
    'only PPE units go to the PAR list' => [[['78000.00', 1]], 'property-management.par', '0 ICS and 1 PAR draft ready to complete.'],
]);

it('says so when a release has no property units waiting for a document', function () {
    $paper = InventoryItems::factory()->create(['is_property' => false, 'requires_expiry' => false, 'is_active' => true]);
    propertyBatch($paper, 'P-1', 10, '250.00', now()->subDay());
    openRelease(approvedFor($paper, 2))->call('release')->assertHasNoErrors();
    $issuance = StockIssuances::sole();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('draftPropertyDocuments', $issuance->id)
        ->assertRedirect(route('property-management.ics', ['issuance' => $issuance->id]));

    expect(session('property-documents-drafted'))->toBe('This issuance has no property units waiting for an ICS or PAR.');
});
