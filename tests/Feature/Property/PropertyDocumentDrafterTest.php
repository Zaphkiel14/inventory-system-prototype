<?php

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\IssuedPropertyUnits;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Models\User;
use App\Services\Property\PropertyDocumentDrafter;
use App\Support\PropertySettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Sam Custodian']));
    $this->travelTo('2026-11-27 09:00:00');
});

/**
 * Release 4 fans drawn from three batches of different cost, oldest first:
 * 1 PPE (78,000), 1 SPHV (6,000), then 2 SPLV (1,196.11).
 */
function releaseMixedFans(): StockIssuances
{
    $fan = InventoryItems::factory()->property(5)->create(['item_code' => 'EQP-0001', 'name' => 'Electric Fan 16"', 'unit' => 'unit', 'requires_expiry' => false]);

    foreach ([['FAN-PPE', 1, '78000.00', 30], ['FAN-SPHV', 1, '6000.00', 20], ['FAN-SPLV', 2, '1196.11', 10]] as [$number, $qty, $cost, $daysAgo]) {
        InventoryBatches::factory()->create([
            'inventory_item_id' => $fan->id,
            'batch_number' => $number,
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'unit_cost' => $cost,
            'received_at' => now()->subDays($daysAgo),
            'expiration_date' => null,
        ]);
    }

    $requisition = Requisitions::factory()->approved()
        ->withLine($fan, 4)
        ->create(['requester_name' => 'Jane Requester', 'fund_cluster' => '01 - Regular Agency Fund'])
        ->load('items');
    $line = $requisition->items->sole();

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set('form.released_by_designation', 'Supply Officer III')
        ->set('form.received_by_designation', 'Nurse II');

    foreach (range(0, 3) as $i) {
        $page->set("form.units.{$line->id}.{$i}.brand", 'Asahi')
            ->set("form.units.{$line->id}.{$i}.model", 'EF-1600')
            ->set("form.units.{$line->id}.{$i}.serial_number", 'SN-000'.($i + 1));
    }

    $page->call('release')->assertHasNoErrors();

    return StockIssuances::sole();
}

/**
 * Each document as [type, ICS class, status, number of lines].
 *
 * @param  iterable<PropertyDocuments>  $documents
 * @return list<array{0: PropertyDocumentType, 1: ?PropertyClass, 2: PropertyDocumentStatus, 3: int}>
 */
function documentSummary(iterable $documents): array
{
    return collect($documents)
        ->map(fn (PropertyDocuments $document) => [$document->type, $document->ics_class, $document->status, $document->lines()->count()])
        ->values()
        ->all();
}

it('drafts an SPLV ICS, an SPHV ICS and a PAR from one issuance, in that order, without moving stock', function () {
    $issuance = releaseMixedFans();
    $stockBefore = [(int) InventoryBatches::sum('remaining_qty'), InventoryTransactions::count()];

    expect($issuance->pendingPropertyUnitsCount())->toBe(4);

    $documents = app(PropertyDocumentDrafter::class)->forIssuance($issuance);

    expect(documentSummary($documents))->toBe([
        [PropertyDocumentType::Ics, PropertyClass::Splv, PropertyDocumentStatus::Draft, 2],
        [PropertyDocumentType::Ics, PropertyClass::Sphv, PropertyDocumentStatus::Draft, 1],
        [PropertyDocumentType::Par, null, PropertyDocumentStatus::Draft, 1],
    ])->and($issuance->pendingPropertyUnitsCount())->toBe(0)
        ->and([(int) InventoryBatches::sum('remaining_qty'), InventoryTransactions::count()])->toBe($stockBefore);
});

it('returns the same drafts without duplicate lines when run twice', function () {
    $issuance = releaseMixedFans();
    $drafter = app(PropertyDocumentDrafter::class);

    $first = $drafter->forIssuance($issuance)->modelKeys();
    $second = $drafter->forIssuance($issuance)->modelKeys();

    expect($second)->toBe($first)
        ->and(PropertyDocuments::count())->toBe(3)
        ->and(PropertyDocumentLines::count())->toBe(4);
});

it('skips units that are already on a document line', function () {
    $issuance = releaseMixedFans();
    $alreadyListed = IssuedPropertyUnits::query()->where('property_class', PropertyClass::Splv)->orderBy('id')->first();
    PropertyDocumentLines::factory()->forUnit($alreadyListed)->create();

    $documents = app(PropertyDocumentDrafter::class)->forIssuance($issuance);

    expect(documentSummary($documents))->toBe([
        [PropertyDocumentType::Ics, PropertyClass::Splv, PropertyDocumentStatus::Draft, 1],
        [PropertyDocumentType::Ics, PropertyClass::Sphv, PropertyDocumentStatus::Draft, 1],
        [PropertyDocumentType::Par, null, PropertyDocumentStatus::Draft, 1],
    ])->and(PropertyDocumentLines::where('issued_property_unit_id', $alreadyListed->id)->count())->toBe(1);
});

it('pre-fills each line from the item, the batch drawn and the unit', function () {
    $issuance = releaseMixedFans();
    $splvBatch = InventoryBatches::where('batch_number', 'FAN-SPLV')->sole();

    $splvIcs = app(PropertyDocumentDrafter::class)->forIssuance($issuance)->first();
    $line = $splvIcs->lines()->orderBy('id')->first();

    expect($line->only([
        'quantity', 'unit', 'unit_cost', 'item_name', 'brand', 'model', 'serial_number',
        'property_number', 'estimated_useful_life_years',
    ]))->toBe([
        'quantity' => 1,
        'unit' => 'unit',
        'unit_cost' => '1196.11',
        'item_name' => 'Electric Fan 16"',
        'brand' => 'Asahi',
        'model' => 'EF-1600',
        'serial_number' => 'SN-0003',
        'property_number' => null,
        'estimated_useful_life_years' => 5,
    ])->and($line->date_acquired->toDateString())->toBe($splvBatch->received_at->toDateString())
        ->and($line->total_cost)->toBe('1196.11')
        ->and($line->issuedPropertyUnit->stockIssuanceBatch->batch_id)->toBe($splvBatch->id)
        ->and($line->inventory_item_id)->toBe($line->issuedPropertyUnit->inventory_item_id);
});

it('pre-fills the header from the issuance and its requisition', function () {
    $issuance = releaseMixedFans();

    $par = app(PropertyDocumentDrafter::class)->forIssuance($issuance)->last();

    expect($par->only([
        'document_number', 'fund_cluster', 'requisition_id', 'stock_issuance_id',
        'from_name', 'from_position', 'to_name', 'to_position',
    ]))->toBe([
        'document_number' => null,
        'fund_cluster' => '01 - Regular Agency Fund',
        'requisition_id' => $issuance->requisition_id,
        'stock_issuance_id' => $issuance->id,
        'from_name' => 'Sam Custodian',
        'from_position' => 'Supply Officer III',
        'to_name' => 'Jane Requester',
        'to_position' => 'Nurse II',
    ])->and([$par->document_date->toDateString(), $par->from_date->toDateString(), $par->to_date->toDateString()])
        ->toBe(['2026-11-27', '2026-11-27', '2026-11-27'])
        ->and($par->finalized_at)->toBeNull();
});

it('refuses to save a document whose ICS class does not fit its type', function (PropertyDocumentType $type, ?PropertyClass $class) {
    expect(fn () => PropertyDocuments::factory()->create(['type' => $type, 'ics_class' => $class]))
        ->toThrow(LogicException::class, 'An ICS needs an SPLV or SPHV class, and a PAR has none.');

    expect(PropertyDocuments::count())->toBe(0);
})->with([
    'an ICS without a class' => [PropertyDocumentType::Ics, null],
    'an ICS classed as PPE' => [PropertyDocumentType::Ics, PropertyClass::Ppe],
    'an ICS classed as consumable' => [PropertyDocumentType::Ics, PropertyClass::Consumable],
    'a PAR with a class' => [PropertyDocumentType::Par, PropertyClass::Sphv],
]);

it('refuses to delete a finalized document or any of its lines', function () {
    $document = PropertyDocuments::factory()->par()->finalized()->create();
    $line = PropertyDocumentLines::factory()->for($document, 'document')->create();

    expect(fn () => $line->delete())->toThrow(LogicException::class, 'Lines of a finalized property document cannot be deleted.')
        ->and(fn () => $document->delete())->toThrow(LogicException::class, 'Finalized property documents cannot be deleted.');

    $this->assertModelExists($document);
    $this->assertModelExists($line);
});

it('deletes a draft together with its lines', function () {
    $document = PropertyDocuments::factory()->ics(PropertyClass::Sphv)->create();
    PropertyDocumentLines::factory()->count(2)->for($document, 'document')->create();
    $otherLine = PropertyDocumentLines::factory()->create();

    $document->delete();

    $this->assertModelMissing($document);
    expect(PropertyDocumentLines::pluck('id')->all())->toBe([$otherLine->id]);
});

it('builds finalized documents whose numbers match the default patterns', function (PropertyDocuments $document, string $pattern, PropertyDocumentType $type, ?PropertyClass $class) {
    expect($document)
        ->type->toBe($type)
        ->ics_class->toBe($class)
        ->status->toBe(PropertyDocumentStatus::Active)
        ->and($document->finalized_at)->not->toBeNull()
        ->and($document->renewal_due_on->toDateString())->toBe('2029-11-27')
        ->and(PropertySettings::matchesPattern($pattern, (string) $document->document_number))->toBeTrue();
})->with([
    'SPLV ICS' => fn () => [PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(), PropertySettings::icsPattern(), PropertyDocumentType::Ics, PropertyClass::Splv],
    'SPHV ICS' => fn () => [PropertyDocuments::factory()->ics(PropertyClass::Sphv)->finalized()->create(), PropertySettings::icsPattern(), PropertyDocumentType::Ics, PropertyClass::Sphv],
    'PAR' => fn () => [PropertyDocuments::factory()->par()->finalized()->create(), PropertySettings::parPattern(), PropertyDocumentType::Par, null],
]);
