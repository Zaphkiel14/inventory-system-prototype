<?php

use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Models\User;
use App\Services\Property\RisDocument;
use Livewire\Livewire;

it('derives Stock Available from what was released and what usable stock covers', function (int $released, int $outstanding, int $usable, string $expected) {
    expect(RisDocument::stockAvailable($released, $outstanding, $usable))->toBe($expected);
})->with([
    'something was released' => [2, 0, 0, 'Yes'],
    'released with more still outstanding and no stock' => [1, 4, 0, 'Yes'],
    'usable stock exactly covers what is outstanding' => [0, 5, 5, 'Yes'],
    'usable stock exceeds what is outstanding' => [0, 5, 9, 'Yes'],
    'usable stock falls short of what is outstanding' => [0, 5, 4, 'No'],
    'nothing released and nothing outstanding' => [0, 0, 10, 'No'],
]);

it('maps the RIS header, lines and signatories from the requisition and its latest issuance', function () {
    $this->actingAs(User::factory()->create(['name' => 'Sam Custodian']));
    $this->travelTo('2026-11-20 08:00:00');

    $fan = InventoryItems::factory()->property()->create(['item_code' => 'EQP-0001', 'name' => 'Electric Fan 16"', 'unit' => 'unit', 'requires_expiry' => false]);
    $paper = InventoryItems::factory()->create(['item_code' => 'OFF-0042', 'name' => 'Bond Paper A4', 'unit' => 'ream', 'is_property' => false, 'requires_expiry' => false]);
    InventoryBatches::factory()->create(['inventory_item_id' => $fan->id, 'received_qty' => 5, 'remaining_qty' => 5, 'unit_cost' => '1196.11', 'expiration_date' => null]);
    InventoryBatches::factory()->create(['inventory_item_id' => $paper->id, 'received_qty' => 1, 'remaining_qty' => 1, 'unit_cost' => '250.00', 'expiration_date' => null]);

    $requisition = Requisitions::factory()->approved()
        ->withLine($fan, 2)
        ->withLine($paper, 3)
        ->create([
            'requester_name' => 'Jane Requester',
            'requester_position' => 'Nurse II',
            'purpose' => 'Fans for the ER',
            'submitted_at' => '2026-11-18 10:00:00',
            'ris_number' => '26102455',
            'fund_cluster' => '01 - Regular Agency Fund',
            'division' => 'Nursing Service Division (NSD)',
            'office' => 'Emergency Unit (EU)',
            'rc_code' => 'RC-11',
            'approved_by_name' => 'Mark Approver',
            'approved_by_designation' => 'Chief Administrative Officer',
            'approved_on' => '2026-11-19',
        ])
        ->load('items');
    [$fanLine, $paperLine] = $requisition->items->all();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$paperLine->id}", 0)
        ->set("form.units.{$fanLine->id}.0.brand", 'Asahi')
        ->set("form.units.{$fanLine->id}.0.model", 'EF-1600')
        ->set("form.units.{$fanLine->id}.0.serial_number", 'SN-0001')
        ->set("form.units.{$fanLine->id}.1.serial_number", 'SN-0002')
        ->set("form.issue_remarks.{$paperLine->id}", 'To follow')
        ->call('release')
        ->assertHasNoErrors();

    StockIssuances::factory()->for($requisition, 'requisition')->create([
        'released_by' => 'Later Custodian',
        'released_by_designation' => 'Supply Officer III',
        'received_by' => 'Juan Receiver',
        'received_by_designation' => 'Nurse I',
        'released_at' => '2026-11-25 14:00:00',
    ]);

    $document = RisDocument::for($requisition->fresh());

    expect($document)
        ->fund_cluster->toBe('01 - Regular Agency Fund')
        ->division->toBe('Nursing Service Division (NSD)')
        ->office->toBe('Emergency Unit (EU)')
        ->rc_code->toBe('RC-11')
        ->ris_number->toBe('26102455')
        ->purpose->toBe('Fans for the ER')
        ->paper_size->toBe('a4');

    expect($document['ris_date']->toDateString())->toBe('2026-11-19');

    expect($document['lines'])->toBe([
        [
            'stock_no' => 'EQP-0001', 'unit' => 'unit', 'description' => 'Electric Fan 16"', 'quantity' => 2,
            'stock_available' => 'Yes', 'issue_qty' => 2, 'remarks' => null,
            'units' => [
                ['brand' => 'Asahi', 'model' => 'EF-1600', 'serial_number' => 'SN-0001'],
                ['brand' => null, 'model' => null, 'serial_number' => 'SN-0002'],
            ],
        ],
        [
            'stock_no' => 'OFF-0042', 'unit' => 'ream', 'description' => 'Bond Paper A4', 'quantity' => 3,
            'stock_available' => 'No', 'issue_qty' => 0, 'remarks' => 'To follow', 'units' => [],
        ],
    ]);

    expect([
        'requested_by' => [$document['requested_by']['name'], $document['requested_by']['designation'], $document['requested_by']['date']->toDateString()],
        'approved_by' => [$document['approved_by']['name'], $document['approved_by']['designation'], $document['approved_by']['date']->toDateString()],
        'issued_by' => [$document['issued_by']['name'], $document['issued_by']['designation'], $document['issued_by']['date']->toDateString()],
        'received_by' => [$document['received_by']['name'], $document['received_by']['designation'], $document['received_by']['date']->toDateString()],
    ])->toBe([
        'requested_by' => ['Jane Requester', 'Nurse II', '2026-11-18'],
        'approved_by' => ['Mark Approver', 'Chief Administrative Officer', '2026-11-19'],
        'issued_by' => ['Later Custodian', 'Supply Officer III', '2026-11-25'],
        'received_by' => ['Juan Receiver', 'Nurse I', '2026-11-25'],
    ]);
});

it('streams the RIS as an inline PDF', function () {
    $this->actingAs(User::factory()->create());
    $requisition = Requisitions::factory()->approved()
        ->withLine(InventoryItems::factory()->create(), 2)
        ->create(['ris_number' => '26102455']);

    $this->get(route('requisitions.ris', $requisition))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=RIS-26102455.pdf');
});

it('redirects a guest to the login page instead of serving the RIS', function () {
    $requisition = Requisitions::factory()->approved()->create();

    $this->get(route('requisitions.ris', $requisition))->assertRedirect(route('login'));
});
