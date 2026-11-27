<?php

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Livewire\PropertyManagement\InventoryCustodianSlips;
use App\Livewire\PropertyManagement\PropertyAcknowledgmentReceipts;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo('2026-11-27 09:00:00');
});

/**
 * Five ICS documents and one PAR:
 *  SPLV-2026-11-001 Active, renewal in 10 days, from RIS 26102455 (Emergency Unit) and the issuance, 2 lines
 *  SPHV-2026-11-002 Active, renewal in 400 days, Pedro Penduko, fund 05 - Trust Fund, an office chair
 *  SPLV-2026-11-003 Active, renewal 5 days overdue
 *  SPLV-2026-11-004 Returned, renewal in 5 days
 *  draft ICS with no number
 *  PAR 2026-11-005 Active, renewal in 10 days
 *
 * @return array{issuance: StockIssuances, first: PropertyDocuments, empty: PropertyDocuments}
 */
function listFixture(): array
{
    $requisition = Requisitions::factory()->approved()->create(['ris_number' => '26102455', 'office' => 'Emergency Unit (EU)']);
    $issuance = StockIssuances::factory()->for($requisition, 'requisition')->create();

    $first = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create([
        'document_number' => 'SPLV-2026-11-001',
        'to_name' => 'Jane Requester',
        'fund_cluster' => '01 - Regular Agency Fund',
        'requisition_id' => $requisition->id,
        'stock_issuance_id' => $issuance->id,
        'renewal_due_on' => today()->addDays(10),
    ]);
    PropertyDocumentLines::factory()->for($first, 'document')->create(['item_name' => 'Electric Fan', 'property_number' => 'LV2026-0001', 'serial_number' => 'SN-0001', 'unit_cost' => '1196.11', 'quantity' => 1]);
    PropertyDocumentLines::factory()->for($first, 'document')->create(['item_name' => 'Wall Clock', 'property_number' => 'LV2026-0002', 'serial_number' => null, 'unit_cost' => '500.00', 'quantity' => 3]);

    $chair = PropertyDocuments::factory()->ics(PropertyClass::Sphv)->finalized()->create([
        'document_number' => 'SPHV-2026-11-002',
        'to_name' => 'Pedro Penduko',
        'fund_cluster' => '05 - Trust Fund',
        'renewal_due_on' => today()->addDays(400),
    ]);
    PropertyDocumentLines::factory()->for($chair, 'document')->create(['item_name' => 'Office Chair', 'property_number' => 'HV2026-0001', 'serial_number' => 'SN-CH-9', 'unit_cost' => '6000.00']);

    $empty = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-003', 'to_name' => 'Ana Reyes', 'renewal_due_on' => today()->subDays(5)]);
    PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-004', 'to_name' => 'Ana Reyes', 'status' => PropertyDocumentStatus::Returned, 'renewal_due_on' => today()->addDays(5)]);
    PropertyDocuments::factory()->ics(PropertyClass::Splv)->create(['to_name' => 'Ana Reyes']);
    PropertyDocuments::factory()->par()->finalized()->create(['document_number' => '2026-11-005', 'to_name' => 'Jane Requester', 'renewal_due_on' => today()->addDays(10)]);

    return ['issuance' => $issuance, 'first' => $first, 'empty' => $empty];
}

/**
 * The document numbers a list currently shows, sorted, with "draft" for drafts.
 *
 * @return list<string>
 */
function listedNumbers(Testable $page): array
{
    return $page->instance()->documents->getCollection()
        ->map(fn (PropertyDocuments $document) => $document->document_number ?? 'draft')
        ->sort()
        ->values()
        ->all();
}

it('lists only ICS documents on the ICS page and only PARs on the PAR page', function () {
    listFixture();

    expect(listedNumbers(Livewire::test(InventoryCustodianSlips::class)))
        ->toBe(['SPHV-2026-11-002', 'SPLV-2026-11-001', 'SPLV-2026-11-003', 'SPLV-2026-11-004', 'draft'])
        ->and(listedNumbers(Livewire::test(PropertyAcknowledgmentReceipts::class)))->toBe(['2026-11-005']);
});

it('renders both list pages', function () {
    listFixture();

    $this->get(route('property-management.ics'))->assertOk();
    $this->get(route('property-management.par'))->assertOk();
});

it('adds each document\'s line count and total', function () {
    ['first' => $first, 'empty' => $empty] = listFixture();

    $documents = Livewire::test(InventoryCustodianSlips::class)->instance()->documents->getCollection()->keyBy('id');

    expect([$documents[$first->id]->lines_count, $documents[$first->id]->lines_total])->toBe([2, '2696.11'])
        ->and([$documents[$empty->id]->lines_count, $documents[$empty->id]->lines_total])->toBe([0, '0.00']);
});

it('searches the document, its lines and its source RIS', function (string $term, array $expected) {
    listFixture();

    expect(listedNumbers(Livewire::test(InventoryCustodianSlips::class)->set('search', $term)))->toBe($expected);
})->with([
    'document number' => ['SPHV-2026', ['SPHV-2026-11-002']],
    'property number' => ['LV2026-0002', ['SPLV-2026-11-001']],
    'serial number' => ['SN-CH-9', ['SPHV-2026-11-002']],
    'item name' => ['Office Chair', ['SPHV-2026-11-002']],
    'accountable person' => ['Pedro', ['SPHV-2026-11-002']],
    'fund cluster' => ['05 - Trust', ['SPHV-2026-11-002']],
    'RIS number' => ['26102455', ['SPLV-2026-11-001']],
    'office' => ['Emergency Unit', ['SPLV-2026-11-001']],
]);

it('filters by status', function (string $status, array $expected) {
    listFixture();

    expect(listedNumbers(Livewire::test(InventoryCustodianSlips::class)->set('status', $status)))->toBe($expected);
})->with([
    'returned' => ['returned', ['SPLV-2026-11-004']],
    'draft' => ['draft', ['draft']],
    'an unknown status shows everything' => ['lost', ['SPHV-2026-11-002', 'SPLV-2026-11-001', 'SPLV-2026-11-003', 'SPLV-2026-11-004', 'draft']],
]);

it('shows active documents due for renewal within 30 days, including overdue ones', function () {
    listFixture();

    expect(listedNumbers(Livewire::test(InventoryCustodianSlips::class)->set('renewalDue', true)))
        ->toBe(['SPLV-2026-11-001', 'SPLV-2026-11-003']);
});

it('shows only the documents drafted from the issuance in the address', function () {
    ['issuance' => $issuance] = listFixture();

    $page = Livewire::withQueryParams(['issuance' => $issuance->id])->test(InventoryCustodianSlips::class);

    expect(listedNumbers($page))->toBe(['SPLV-2026-11-001']);

    expect(listedNumbers($page->call('clearIssuance')))->toHaveCount(5);
});
