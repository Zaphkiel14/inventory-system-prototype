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

    Livewire::test(Issuances::class)
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

    Livewire::test(Issuances::class)
        ->call('open', $requisition->id)
        ->assertSet("form.release.{$line->id}", 5) // capped by stock on hand
        ->assertSet('form.received_by', 'Jane Requester');
});

test('items without expiry are released FIFO, oldest batch first', function () {
    $newer = batchOf($this->paper, 'P-NEW', 10, now());
    $older = batchOf($this->paper, 'P-OLD', 4, now()->subDays(5));
    $requisition = approvedRequest([[$this->paper, 6]]);

    Livewire::test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    expect($older->fresh()->remaining_qty)->toBe(0)
        ->and($newer->fresh()->remaining_qty)->toBe(8);
});

test('items with expiry are released FEFO and expired batches are skipped', function () {
    $expired = batchOf($this->alcohol, 'A-EXP', 10, now()->subDays(30), today()->subDay());
    $later = batchOf($this->alcohol, 'A-LATE', 10, now()->subDays(20), today()->addMonths(6));
    $sooner = batchOf($this->alcohol, 'A-SOON', 3, now(), today()->addWeek()); // newest, but expires first
    $requisition = approvedRequest([[$this->alcohol, 5]]);

    Livewire::test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

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

    Livewire::test(Issuances::class)
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

    Livewire::test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 12)
        ->call('release')
        ->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::PartiallyIssued)
        ->and($line->fresh()->released_qty)->toBe(12)
        ->and($line->fresh()->outstanding_qty)->toBe(8);

    Livewire::test(Issuances::class)
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

    Livewire::test(Issuances::class)
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

    Livewire::test(Issuances::class)
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

    Livewire::test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 0)
        ->call('release')
        ->assertHasErrors('form.release');
});

test('requests that are not approved cannot be released', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);

    $page = Livewire::test(Issuances::class)->call('open', $requisition->id);
    $requisition->update(['status' => RequisitionStatus::Issued]);

    $page->call('release')->assertHasErrors('form.requisition_id');

    expect(StockIssuances::count())->toBe(0);
});

test('released stock is no longer counted as committed for approvals', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 20]]);

    expect((int) InventoryItems::whereKey($this->paper->id)->withCommittedQty()->value('committed_qty'))->toBe(20);

    Livewire::test(Issuances::class)->call('open', $requisition->id)->call('release');

    $item = InventoryItems::whereKey($this->paper->id)->withCommittedQty()->withAvailableQty()->first();
    expect((int) $item->committed_qty)->toBe(0)
        ->and((int) $item->available_qty)->toBe(30);
});

test('issuances are permanent', function () {
    batchOf($this->paper, 'P-1', 50, now());
    $requisition = approvedRequest([[$this->paper, 5]]);
    Livewire::test(Issuances::class)->call('open', $requisition->id)->call('release');

    expect(fn () => StockIssuances::sole()->update(['received_by' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => StockIssuances::sole()->delete())->toThrow(LogicException::class);
});
