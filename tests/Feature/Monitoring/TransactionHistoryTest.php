<?php

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Livewire\Monitoring\TransactionHistory;
use App\Models\InventoryAdjustments;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Carbon\CarbonInterface;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Jane Clerk']);
    $this->other = User::factory()->create(['name' => 'Omar Storekeeper']);
    $this->actingAs($this->user);

    InventoryCategories::factory()->create();

    $this->receipt = StockReceipts::factory()->create([
        'supplier_id' => Suppliers::factory()->create(['name' => 'Acme Supply'])->id,
        'received_by' => $this->user->id,
        'reference_number' => 'DR-555001',
        'purchase_order_number' => 'PO-2026-00042',
    ]);

    $this->item = InventoryItems::factory()->create(['name' => 'Copy Paper', 'item_code' => 'INV-0001', 'unit' => 'Ream']);
    $this->batch = InventoryBatches::factory()->create([
        'inventory_item_id' => $this->item->id,
        'stock_receipt_id' => $this->receipt->id,
        'storage_location_id' => StorageLocations::factory()->create()->id,
        'batch_number' => 'BAT-PAPER-1',
        'lot_number' => 'LOT-PAPER-1',
        'received_qty' => 100,
        'remaining_qty' => 100,
    ]);

    $requisition = Requisitions::create([
        'request_number' => 'REQ-202611-0007',
        'user_id' => $this->user->id,
        'status' => RequisitionStatus::Issued,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now()->subDays(3),
    ]);
    $this->issuance = StockIssuances::create([
        'requisition_id' => $requisition->id,
        'user_id' => $this->user->id,
        'issuance_number' => 'ISS-202611-0003',
        'released_by' => 'Jane Clerk',
        'received_by' => 'Ana Requester',
        'released_at' => now()->subDays(2),
    ]);

    $this->adjustment = InventoryAdjustments::create([
        'batch_id' => $this->batch->id,
        'user_id' => $this->other->id,
        'type' => AdjustmentType::Damage,
        'requested_by' => 'Omar',
        'approved_by' => 'Boss',
        'previous_qty' => 70,
        'changed_qty' => -5,
        'new_qty' => 65,
        'reason' => 'Water damage',
    ]);

    $this->receiptTxn = historyEntry(InventoryTransactionType::Receipt, 100, StockReceipts::class, $this->receipt->id, now()->subDays(5));
    $this->issueTxn = historyEntry(InventoryTransactionType::Issuance, -30, StockIssuances::class, $this->issuance->id, now()->subDays(2));
    $this->adjustTxn = historyEntry(InventoryTransactionType::Adjustment, -5, InventoryAdjustments::class, $this->adjustment->id, now()->subDay(), $this->other);
    $this->oldTxn = historyEntry(InventoryTransactionType::Receipt, 7, StockReceipts::class, $this->receipt->id, now()->subDays(60));
});

function historyEntry(InventoryTransactionType $type, int $qty, string $refType, int $refId, CarbonInterface $at, ?User $by = null): InventoryTransactions
{
    $t = new InventoryTransactions([
        'inventory_item_id' => test()->item->id,
        'batch_id' => test()->batch->id,
        'user_id' => ($by ?? test()->user)->id,
        'type' => $type,
        'quantity' => $qty,
        'balance_after' => 0,
        'reference_type' => $refType,
        'reference_id' => $refId,
    ]);
    $t->created_at = $at;
    $t->save();

    return $t;
}

function historyIds($component): array
{
    return $component->instance()->transactions->pluck('id')->all();
}

test('the transaction history page renders with references resolved', function () {
    $this->get(route('monitoring.transaction-history'))
        ->assertOk()
        ->assertSee('Transaction History')
        ->assertSee('DR-555001')
        ->assertSee('Acme Supply')
        ->assertSee('ISS-202611-0003')
        ->assertSee('REQ-202611-0007')
        ->assertSee('Water damage');
});

test('the default range is the last 30 days, newest first', function () {
    $component = Livewire::test(TransactionHistory::class);

    expect(historyIds($component))->toBe([$this->adjustTxn->id, $this->issueTxn->id, $this->receiptTxn->id]);

    $component->call('toggleOrder');
    expect(historyIds($component))->toBe([$this->receiptTxn->id, $this->issueTxn->id, $this->adjustTxn->id]);

    $component->set('range', 'all');
    expect(historyIds($component))->toContain($this->oldTxn->id);
});

test('totals cover every matching entry', function () {
    expect(Livewire::test(TransactionHistory::class)->instance()->totals)
        ->toBe(['entries' => 3, 'in' => 100, 'out' => 35, 'net' => 65]);
});

test('type, direction and user filters narrow the ledger', function () {
    $component = Livewire::test(TransactionHistory::class);

    $component->set('type', 'issuance');
    expect(historyIds($component))->toBe([$this->issueTxn->id]);

    $component->set('type', '')->set('direction', 'in');
    expect(historyIds($component))->toBe([$this->receiptTxn->id]);

    $component->set('direction', '')->set('user', (string) $this->other->id);
    expect(historyIds($component))->toBe([$this->adjustTxn->id]);

    $component->set('type', 'bogus')->assertSet('type', '');
});

test('search matches items, batches and the documents behind entries', function (string $term, string $expected) {
    $component = Livewire::test(TransactionHistory::class)->set('search', $term);

    $ids = historyIds($component);
    $map = ['receipt' => $this->receiptTxn->id, 'issuance' => $this->issueTxn->id];

    expect($ids)->toBe([$map[$expected]]);
})->with([
    'delivery number' => ['DR-555001', 'receipt'],
    'PO number' => ['PO-2026-00042', 'receipt'],
    'issuance number' => ['ISS-202611-0003', 'issuance'],
    'request number' => ['REQ-202611-0007', 'issuance'],
]);

test('search by item or batch matches every entry for it', function () {
    expect(historyIds(Livewire::test(TransactionHistory::class)->set('search', 'copy pap')))->toHaveCount(3)
        ->and(historyIds(Livewire::test(TransactionHistory::class)->set('search', 'LOT-PAPER')))->toHaveCount(3)
        ->and(historyIds(Livewire::test(TransactionHistory::class)->set('search', 'nothing-like-this')))->toBe([]);
});

test('custom ranges filter by the given dates', function () {
    $component = Livewire::test(TransactionHistory::class)
        ->set('range', 'custom')
        ->set('from', today()->subDays(70)->toDateString())
        ->set('to', today()->subDays(50)->toDateString());

    expect(historyIds($component))->toBe([$this->oldTxn->id]);
});

test('the export streams the filtered ledger as CSV', function () {
    $response = Livewire::test(TransactionHistory::class)
        ->set('type', 'adjustment')
        ->call('export')
        ->assertFileDownloaded();

    $csv = $response->effects['download']['content'] ?? '';
    $csv = base64_decode($csv, true) ?: $csv;

    expect($csv)->toContain('Date,Type,"Item Code"')
        ->and($csv)->toContain('Damage')
        ->and($csv)->toContain('Water damage')
        ->and($csv)->not->toContain('DR-555001');
});
