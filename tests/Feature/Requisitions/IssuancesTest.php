<?php

use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockIssuanceBatches;
use App\Models\StockIssuances;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->requester = User::factory()->create(['name' => 'Jane Requester']);
    $this->custodian = User::factory()->create(['name' => 'Sam Custodian']);
    $this->actingAs($this->custodian);

    InventoryCategories::factory()->create();

    $this->receipt = StockReceipts::create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->custodian->id,
        'reference_number' => 'DR-001',
        'received_date' => today(),
        'delivery_date' => now(),
        'status' => 'complete_delivery',
    ]);

    $this->location = StorageLocations::factory()->create();

    // FIFO item: no expiry.
    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'requires_expiry' => false, 'is_active' => true]);
    // FEFO item: requires expiry.
    $this->alcohol = InventoryItems::factory()->create(['name' => 'Alcohol 70%', 'requires_expiry' => true, 'is_active' => true]);
});

function batchOf(InventoryItems $item, string $number, int $qty, $receivedAt, $expires = null): InventoryBatches
{
    return InventoryBatches::create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => test()->receipt->id,
        'storage_location_id' => test()->location->id,
        'batch_number' => $number,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'expiration_date' => $expires,
        'received_at' => $receivedAt,
        'unit_cost' => 10,
    ]);
}

/**
 * @param  array<int, array{0: InventoryItems, 1: int}>  $lines  [item, approved qty]
 */
function approvedRequest(array $lines): Requisitions
{
    static $n = 0;
    $n++;

    $requisition = Requisitions::create([
        'request_number' => sprintf('REQ-TEST-%04d', $n),
        'user_id' => test()->requester->id,
        'status' => RequisitionStatus::Approved,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now(),
    ]);

    foreach ($lines as [$item, $qty]) {
        $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'requested_qty' => $qty,
            'available_qty' => $qty,
            'approved_qty' => $qty,
            'released_qty' => 0,
            'outstanding_qty' => $qty,
        ]);
    }

    return $requisition->load('items');
}

test('the issuances page renders', function () {
    $this->get(route('requisitions.issuances'))->assertOk()->assertSee('Requisition Issuance');
});

test('the queue only lists requests with approved stock to release', function () {
    approvedRequest([[$this->paper, 1]])->update(['purpose' => 'Ready one']);
    approvedRequest([[$this->paper, 1]])->update(['purpose' => 'Pending one', 'status' => RequisitionStatus::Pending]);
    approvedRequest([[$this->paper, 1]])->update(['purpose' => 'Done one', 'status' => RequisitionStatus::Issued]);

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->assertSee('Ready one')
        ->assertDontSee('Pending one')
        ->assertDontSee('Done one')
        ->set('status', '')
        ->assertSee('Done one')
        ->assertDontSee('Pending one');
});

test('opening a request pre-fills outstanding quantities and the requester as receiver', function () {
    batchOf($this->paper, 'P-1', 5, now());
    $requisition = approvedRequest([[$this->paper, 8]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->assertSet("form.release.{$line->id}", 5) // capped by stock on hand
        ->assertSet('form.received_by', 'Jane Requester');
});

test('items without expiry are released FIFO, oldest batch first', function () {
    $newer = batchOf($this->paper, 'P-NEW', 10, now());
    $older = batchOf($this->paper, 'P-OLD', 4, now()->subDays(5));
    $requisition = approvedRequest([[$this->paper, 6]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    expect($older->fresh()->remaining_qty)->toBe(0)
        ->and($newer->fresh()->remaining_qty)->toBe(8);
});

test('items with expiry are released FEFO and expired batches are skipped', function () {
    $expired = batchOf($this->alcohol, 'A-EXP', 10, now()->subDays(30), today()->subDay());
    $later = batchOf($this->alcohol, 'A-LATE', 10, now()->subDays(20), today()->addMonths(6));
    $sooner = batchOf($this->alcohol, 'A-SOON', 3, now(), today()->addWeek()); // newest, but expires first
    $requisition = approvedRequest([[$this->alcohol, 5]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    expect($sooner->fresh()->remaining_qty)->toBe(0)
        ->and($later->fresh()->remaining_qty)->toBe(8)
        ->and($expired->fresh()->remaining_qty)->toBe(10);

    expect(StockIssuanceBatches::orderBy('id')->get()->map(fn ($b) => [$b->batch_id, $b->quantity])->all())
        ->toBe([[$sooner->id, 3], [$later->id, 2]]);
});

test('releasing records the issuance, stock transactions and every quantity', function () {
    $batch = batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 20]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set('form.received_by', 'Juan Receiver')
        ->set('form.remarks', 'Picked up at counter')
        ->call('release')
        ->assertHasNoErrors();

    $issuance = StockIssuances::sole();
    expect($issuance->issuance_number)->toBe('ISS-'.now()->format('Ym').'-0001')
        ->and($issuance->released_by)->toBe('Sam Custodian')
        ->and($issuance->user_id)->toBe($this->custodian->id)
        ->and($issuance->received_by)->toBe('Juan Receiver')
        ->and($issuance->remarks)->toBe('Picked up at counter')
        ->and($issuance->stockIssuanceItems()->sole()->released_qty)->toBe(20);

    $line->refresh();
    expect($line->requested_qty)->toBe(20)
        ->and($line->approved_qty)->toBe(20)
        ->and($line->released_qty)->toBe(20)
        ->and($line->outstanding_qty)->toBe(0)
        ->and($requisition->fresh()->status)->toBe(RequisitionStatus::Issued)
        ->and($batch->fresh()->remaining_qty)->toBe(30);

    $transaction = InventoryTransactions::sole();
    expect($transaction->type)->toBe(InventoryTransactionType::Issuance)
        ->and($transaction->quantity)->toBe(-20)
        ->and($transaction->balance_after)->toBe(30)
        ->and((int) $transaction->reference_id)->toBe($issuance->id);
});

test('a requisition can be released over several issuances', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 20]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 12)
        ->call('release')
        ->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::PartiallyIssued)
        ->and($line->fresh()->released_qty)->toBe(12)
        ->and($line->fresh()->outstanding_qty)->toBe(8);

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->assertSet("form.release.{$line->id}", 8)
        ->call('release')
        ->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Issued)
        ->and($line->fresh()->released_qty)->toBe(20)
        ->and($line->fresh()->outstanding_qty)->toBe(0)
        ->and(StockIssuances::count())->toBe(2);
});

test('cannot release more than is outstanding', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 6)
        ->call('release')
        ->assertHasErrors("form.release.{$line->id}");

    expect(StockIssuances::count())->toBe(0);
});

test('a shortfall on one line releases nothing at all', function () {
    $paperBatch = batchOf($this->paper, 'P-1', 50, now());
    batchOf($this->alcohol, 'A-1', 2, now(), today()->addYear());
    $requisition = approvedRequest([[$this->paper, 5], [$this->alcohol, 4]]);
    [$paper, $alcohol] = $requisition->items;

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$alcohol->id}", 4)
        ->call('release')
        ->assertHasErrors("form.release.{$alcohol->id}");

    expect(StockIssuances::count())->toBe(0)
        ->and(InventoryTransactions::count())->toBe(0)
        ->and($paperBatch->fresh()->remaining_qty)->toBe(50)
        ->and($paper->fresh()->released_qty)->toBe(0);
});

test('releasing nothing is refused', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 0)
        ->call('release')
        ->assertHasErrors('form.release');
});

test('requests that are not approved cannot be released', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
    $requisition->update(['status' => RequisitionStatus::Issued]);

    $page->call('release')->assertHasErrors('form.requisition_id');

    expect(StockIssuances::count())->toBe(0);
});

test('released stock is no longer counted as committed for approvals', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 20]]);

    expect((int) InventoryItems::whereKey($this->paper->id)->withCommittedQty()->value('committed_qty'))->toBe(20);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release');

    $item = InventoryItems::whereKey($this->paper->id)->withCommittedQty()->withAvailableQty()->first();
    expect((int) $item->committed_qty)->toBe(0)
        ->and((int) $item->available_qty)->toBe(30);
});

test('issuances are permanent', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);
    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release');

    expect(fn () => StockIssuances::sole()->update(['received_by' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => StockIssuances::sole()->delete())->toThrow(LogicException::class);
});

test('releasing asks for confirmation in a modal instead of a browser dialog', function () {
    batchOf($this->paper, 'P-OLD', 4, now()->subDays(5));
    batchOf($this->paper, 'P-NEW', 10, now());
    $requisition = approvedRequest([[$this->paper, 6]]);

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);

    expect($page->html())->not->toContain('wire:confirm');

    $page->call('confirmRelease')
        ->assertHasNoErrors()
        ->assertDispatched('modal-show', name: 'confirm-release')
        ->assertSee('Release items for '.$requisition->request_number.'?')
        ->assertSee('4 from P-OLD')
        ->assertSee('2 from P-NEW')
        ->assertSee('Confirm Release');

    expect(StockIssuances::count())->toBe(0);

    $page->call('release')
        ->assertHasNoErrors()
        ->assertDispatched('modal-close', name: 'confirm-release');

    expect(StockIssuances::count())->toBe(1);
});

test('the release confirmation does not open while required fields are missing', function () {
    batchOf($this->paper, 'P-1', 10, now());
    $requisition = approvedRequest([[$this->paper, 3]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set('form.received_by', '')
        ->call('confirmRelease')
        ->assertHasErrors(['form.received_by' => 'required'])
        ->assertNotDispatched('modal-show', name: 'confirm-release');

    expect(StockIssuances::count())->toBe(0);
});

/**
 * The item's issuance ledger rows, oldest first, as [batch id, quantity, balance after].
 *
 * @return list<array{0: int, 1: int, 2: int}>
 */
function issuanceLedger(InventoryItems $item): array
{
    return InventoryTransactions::query()
        ->where('inventory_item_id', $item->id)
        ->where('type', InventoryTransactionType::Issuance)
        ->orderBy('id')
        ->get()
        ->map(fn (InventoryTransactions $row) => [(int) $row->batch_id, $row->quantity, $row->balance_after])
        ->all();
}

test('balance_after counts down with each batch drawn and ends at the stock left', function () {
    $first = batchOf($this->paper, 'P-1', 4, now()->subDays(4));
    $second = batchOf($this->paper, 'P-2', 4, now()->subDays(3));
    $third = batchOf($this->paper, 'P-3', 4, now()->subDays(2));
    batchOf($this->paper, 'P-4', 5, now()->subDay());
    $requisition = approvedRequest([[$this->paper, 11]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    expect(issuanceLedger($this->paper))->toBe([
        [$first->id, -4, 13],
        [$second->id, -4, 9],
        [$third->id, -3, 6],
    ])->and((int) InventoryBatches::where('inventory_item_id', $this->paper->id)->sum('remaining_qty'))->toBe(6);
});

test('balance_after stays a running total when two lines draw on the same item', function () {
    $first = batchOf($this->paper, 'P-1', 4, now()->subDays(3));
    $second = batchOf($this->paper, 'P-2', 4, now()->subDays(2));
    $third = batchOf($this->paper, 'P-3', 4, now()->subDay());
    $requisition = approvedRequest([[$this->paper, 5], [$this->paper, 4]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    expect([$first->fresh()->remaining_qty, $second->fresh()->remaining_qty, $third->fresh()->remaining_qty])
        ->toBe([0, 0, 3], 'the second line must not draw again from a batch the first line emptied');
    expectNoNegativeBatches();

    expect(issuanceLedger($this->paper))->toBe([
        [$first->id, -4, 8],
        [$second->id, -1, 7],
        [$second->id, -3, 4],
        [$third->id, -1, 3],
    ])->and((int) InventoryBatches::where('inventory_item_id', $this->paper->id)->sum('remaining_qty'))->toBe(3);
});

test('a release writes one signed ledger row for every batch it draws from', function () {
    batchOf($this->paper, 'P-1', 3, now()->subDays(2));
    batchOf($this->paper, 'P-2', 10, now()->subDay());
    batchOf($this->alcohol, 'A-1', 2, now(), today()->addWeek());
    batchOf($this->alcohol, 'A-2', 10, now(), today()->addMonth());
    $requisition = approvedRequest([[$this->paper, 5], [$this->alcohol, 4]]);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    $issuance = StockIssuances::sole();
    $drawn = StockIssuanceBatches::query()
        ->whereHas('stockIssuanceItem', fn ($query) => $query->where('stock_issuance_id', $issuance->id))
        ->orderBy('id')
        ->get()
        ->map(fn (StockIssuanceBatches $draw) => [(int) $draw->batch_id, -$draw->quantity])
        ->all();
    $ledger = InventoryTransactions::query()
        ->where('reference_type', StockIssuances::class)
        ->where('reference_id', $issuance->id)
        ->orderBy('id')
        ->get()
        ->map(fn (InventoryTransactions $row) => [(int) $row->batch_id, $row->quantity])
        ->all();

    expectNoNegativeBatches();
    expect($drawn)->toHaveCount(4)
        ->and($ledger)->toBe($drawn)
        ->and(collect($ledger)->every(fn (array $row) => $row[1] < 0))->toBeTrue();
});

test('the release preview never offers an expired batch', function () {
    batchOf($this->alcohol, 'A-EXP', 50, now()->subDays(30), today()->subDay());
    batchOf($this->alcohol, 'A-SOON', 3, now(), today()->addWeek());
    batchOf($this->alcohol, 'A-LATE', 10, now(), today()->addMonths(3));
    $requisition = approvedRequest([[$this->alcohol, 5]]);
    $line = $requisition->items->first();

    $preview = Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->instance()
        ->preview;

    expect($preview[$line->id]['allocations']->map(fn (array $draw) => [$draw['batch']->batch_number, $draw['quantity']])->all())
        ->toBe([['A-SOON', 3], ['A-LATE', 2]])
        ->and($preview[$line->id]['shortfall'])->toBe(0);
});

test('the preview of a mixed FEFO and FIFO release matches the batches actually drawn', function () {
    $paperOld = batchOf($this->paper, 'P-OLD', 3, now()->subDays(5));
    $paperNew = batchOf($this->paper, 'P-NEW', 10, now()->subDay());
    batchOf($this->alcohol, 'A-EXP', 20, now()->subDays(40), today()->subDay());
    $alcoholSoon = batchOf($this->alcohol, 'A-SOON', 2, now(), today()->addWeek());
    $alcoholLate = batchOf($this->alcohol, 'A-LATE', 10, now()->subDays(10), today()->addMonths(4));
    $requisition = approvedRequest([[$this->paper, 7], [$this->alcohol, 5]]);

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
    $previewed = collect($page->instance()->preview)
        ->map(fn (array $plan) => $plan['allocations']->map(fn (array $draw) => [$draw['batch']->id, $draw['quantity']])->all())
        ->all();

    $page->call('release')->assertHasNoErrors();

    $drawn = StockIssuanceBatches::with('stockIssuanceItem')
        ->orderBy('id')
        ->get()
        ->groupBy(fn (StockIssuanceBatches $draw) => $draw->stockIssuanceItem->requisition_item_id)
        ->map(fn ($draws) => $draws->map(fn (StockIssuanceBatches $draw) => [(int) $draw->batch_id, $draw->quantity])->all())
        ->all();

    [$paperLine, $alcoholLine] = $requisition->items->all();

    expectNoNegativeBatches();
    expect($previewed)->toEqual($drawn)
        ->and($previewed[$paperLine->id])->toBe([[$paperOld->id, 3], [$paperNew->id, 4]])
        ->and($previewed[$alcoholLine->id])->toBe([[$alcoholSoon->id, 2], [$alcoholLate->id, 3]]);
});

/**
 * Releases may never leave a batch below zero, whatever the lines look like.
 */
function expectNoNegativeBatches(): void
{
    expect(InventoryBatches::query()->where('remaining_qty', '<', 0)->pluck('batch_number')->all())
        ->toBe([], 'no batch may be left with negative stock');
}

test('two lines for the same item preview cumulatively and match what is drawn', function () {
    $first = batchOf($this->paper, 'P-1', 4, now()->subDays(3));
    $second = batchOf($this->paper, 'P-2', 4, now()->subDays(2));
    $third = batchOf($this->paper, 'P-3', 4, now()->subDay());
    $requisition = approvedRequest([[$this->paper, 5], [$this->paper, 4]]);
    [$lineOne, $lineTwo] = $requisition->items->all();

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
    $previewed = collect($page->instance()->preview)
        ->map(fn (array $plan) => $plan['allocations']->map(fn (array $draw) => [$draw['batch']->id, $draw['quantity']])->all())
        ->all();

    expect($previewed)->toBe([
        $lineOne->id => [[$first->id, 4], [$second->id, 1]],
        $lineTwo->id => [[$second->id, 3], [$third->id, 1]],
    ]);

    $page->call('release')->assertHasNoErrors();

    $drawn = StockIssuanceBatches::with('stockIssuanceItem')
        ->orderBy('id')
        ->get()
        ->groupBy(fn (StockIssuanceBatches $draw) => $draw->stockIssuanceItem->requisition_item_id)
        ->map(fn ($draws) => $draws->map(fn (StockIssuanceBatches $draw) => [(int) $draw->batch_id, $draw->quantity])->all())
        ->all();

    expectNoNegativeBatches();
    expect($drawn)->toEqual($previewed);
});

test('lines for one item that together exceed its stock release nothing at all', function () {
    $batches = [
        batchOf($this->paper, 'P-1', 4, now()->subDays(3)),
        batchOf($this->paper, 'P-2', 4, now()->subDays(2)),
        batchOf($this->paper, 'P-3', 4, now()->subDay()),
    ];
    $requisition = approvedRequest([[$this->paper, 8], [$this->paper, 8]]);
    [$lineOne, $lineTwo] = $requisition->items->all();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$lineOne->id}", 8)
        ->set("form.release.{$lineTwo->id}", 8)
        ->call('release')
        ->assertHasErrors();

    expect(collect($batches)->map(fn (InventoryBatches $batch) => $batch->fresh()->remaining_qty)->all())->toBe([4, 4, 4])
        ->and(StockIssuances::count())->toBe(0)
        ->and(StockIssuanceBatches::count())->toBe(0)
        ->and(InventoryTransactions::count())->toBe(0)
        ->and($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
});
