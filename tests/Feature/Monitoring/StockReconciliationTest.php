<?php

use App\Enums\InventoryTransactionType;
use App\Livewire\Monitoring\StockReconciliation;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Services\Inventory\StockReconciliation as Service;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-06-15 12:00:00', 'Asia/Manila'));

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    InventoryCategories::factory()->create();

    $this->supplier = Suppliers::factory()->create();
    $this->location = StorageLocations::factory()->create(['name' => 'Main Storage Room']);
    $this->item = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true, 'requires_expiry' => false]);
});

function rcPeriod(string $from, string $to): array
{
    return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
}

function rcService(): Service
{
    return new Service;
}

function rcRow(string $from, string $to, ?int $itemId = null, array $filters = []): ?array
{
    return rcService()->summary(rcPeriod($from, $to), $filters)
        ->firstWhere('item_id', $itemId ?? test()->item->id);
}

/** Writes a ledger row whose balance_after is the item's real summed remaining_qty. */
function rcLedger(InventoryBatches $batch, InventoryTransactionType $type, int $qty, CarbonInterface $at): InventoryTransactions
{
    $balance = (int) InventoryBatches::where('inventory_item_id', $batch->inventory_item_id)->sum('remaining_qty');

    $t = new InventoryTransactions([
        'inventory_item_id' => $batch->inventory_item_id,
        'batch_id' => $batch->id,
        'user_id' => test()->user->id,
        'type' => $type,
        'quantity' => $qty,
        'balance_after' => $balance,
        'reference_type' => 'test',
        'reference_id' => 1,
    ]);
    $t->created_at = $at;
    $t->save();

    return $t;
}

function rcReceive(InventoryItems $item, int $qty, CarbonInterface $at, ?StorageLocations $location = null, bool $withLedger = true): InventoryBatches
{
    $batch = InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => StockReceipts::factory()->create([
            'supplier_id' => test()->supplier->id,
            'received_by' => test()->user->id,
            'received_date' => $at,
        ])->id,
        'storage_location_id' => ($location ?? test()->location)->id,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'received_at' => $at,
        'expiration_date' => null,
    ]);

    if ($withLedger) {
        rcLedger($batch, InventoryTransactionType::Receipt, $qty, $at);
    }

    return $batch;
}

function rcIssue(InventoryBatches $batch, int $qty, CarbonInterface $at, ?int $pivotQty = null): InventoryTransactions
{
    $batch->decrement('remaining_qty', $qty);
    $tx = rcLedger($batch->fresh(), InventoryTransactionType::Issuance, -$qty, $at);

    $now = $at->toDateTimeString();
    $n = $tx->id;
    $reqId = DB::table('requisitions')->insertGetId([
        'request_number' => "REQ-$n", 'tracking_code' => "TRK-$n", 'requester_name' => 'Tester',
        'status' => 'issued', 'purpose' => 'test', 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $reqItemId = DB::table('requisition_items')->insertGetId([
        'requisition_id' => $reqId, 'inventory_item_id' => $batch->inventory_item_id, 'requested_qty' => $qty,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    $issId = DB::table('stock_issuances')->insertGetId([
        'requisition_id' => $reqId, 'issuance_number' => "ISS-$n", 'released_by' => 'a', 'received_by' => 'b',
        'released_at' => $now, 'created_at' => $now, 'updated_at' => $now,
    ]);
    $issItemId = DB::table('stock_issuance_items')->insertGetId([
        'stock_issuance_id' => $issId, 'requisition_item_id' => $reqItemId, 'released_qty' => $qty,
        'created_at' => $now, 'updated_at' => $now,
    ]);
    DB::table('stock_issuance_batches')->insert([
        'stock_issuance_item_id' => $issItemId,
        'batch_id' => $batch->id,
        'quantity' => $pivotQty ?? $qty,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return $tx;
}

function rcAdjust(InventoryBatches $batch, int $delta, CarbonInterface $at): InventoryTransactions
{
    $batch->increment('remaining_qty', $delta);

    return rcLedger($batch->fresh(), InventoryTransactionType::Adjustment, $delta, $at);
}

function rcSnapshot(): array
{
    return [
        InventoryTransactions::count(),
        (int) InventoryBatches::sum('remaining_qty'),
        InventoryBatches::orderBy('id')->pluck('remaining_qty', 'id')->all(),
    ];
}

function rcCleanFlow(): InventoryBatches
{
    $b = rcReceive(test()->item, 100, Carbon::parse('2026-06-01 09:00'));
    rcReceive(test()->item, 50, Carbon::parse('2026-06-12 09:00'));
    rcIssue($b, 30, Carbon::parse('2026-06-13 09:00'));
    rcAdjust($b, -5, Carbon::parse('2026-06-14 09:00'));

    return $b;
}

test('a clean receive, issue and adjust flow is balanced with no exceptions', function () {
    rcCleanFlow();

    $row = rcRow('2026-06-10', '2026-06-15');

    expect($row)->toMatchArray([
        'opening' => 100,
        'received' => 50,
        'issued' => 30,
        'adjusted' => -5,
        'expected' => 115,
        'actual' => 115,
        'actual_source' => 'batches',
        'variance' => 0,
        'status' => 'balanced',
        'exception_count' => 0,
    ]);
    expect(rcService()->exceptions(rcPeriod('2026-06-01', '2026-06-15')))->toBeEmpty();

    $totals = rcService()->totals(rcService()->summary(rcPeriod('2026-06-10', '2026-06-15')));
    expect($totals)->toMatchArray(['items' => 1, 'balanced' => 1, 'short' => 0, 'overflow' => 0, 'variance_units' => 0]);
});

test('a batch decremented with no ledger row is short and flagged', function () {
    $b = rcCleanFlow();
    $b->decrement('remaining_qty', 7);

    $row = rcRow('2026-06-10', '2026-06-15');
    expect($row['variance'])->toBe(-7)
        ->and($row['status'])->toBe('short')
        ->and($row['actual'])->toBe(108);

    $ex = rcService()->exceptions(rcPeriod('2026-06-10', '2026-06-15'))->firstWhere('kind', 'batch_unbalanced');
    expect($ex)->not->toBeNull()
        ->and($ex['batch_id'])->toBe($b->id)
        ->and($ex['expected'])->toBe(65)
        ->and($ex['actual'])->toBe(58)
        ->and($ex['difference'])->toBe(-7)
        ->and($row['exception_count'])->toBeGreaterThan(0);
});

test('a batch incremented with no ledger row is overflow', function () {
    $b = rcCleanFlow();
    $b->increment('remaining_qty', 4);

    $row = rcRow('2026-06-10', '2026-06-15');
    expect($row['variance'])->toBe(4)
        ->and($row['status'])->toBe('overflow');
    expect(rcService()->exceptions(rcPeriod('2026-06-10', '2026-06-15'))->pluck('kind'))->toContain('batch_unbalanced');
});

test('a tampered balance_after yields a balance_break with the correct expected and actual', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    $issue = rcIssue($b, 30, Carbon::parse('2026-06-05 09:00'));
    rcAdjust($b, -5, Carbon::parse('2026-06-06 09:00'));

    $issue->forceFill(['balance_after' => 60])->saveQuietly(); // should be 70

    $break = rcService()->exceptions(rcPeriod('2026-06-01', '2026-06-15'))
        ->where('kind', 'balance_break')
        ->firstWhere('transaction_id', $issue->id);

    expect($break)->not->toBeNull()
        ->and($break['expected'])->toBe(70)
        ->and($break['actual'])->toBe(60)
        ->and($break['difference'])->toBe(-10)
        ->and($break['item_id'])->toBe($this->item->id);
});

test('a batch without a Receipt transaction is a receipt_mismatch', function () {
    $b = rcReceive($this->item, 40, Carbon::parse('2026-06-12 09:00'), withLedger: false);

    $ex = rcService()->exceptions(rcPeriod('2026-06-10', '2026-06-15'))->firstWhere('kind', 'receipt_mismatch');

    expect($ex)->not->toBeNull()
        ->and($ex['batch_id'])->toBe($b->id)
        ->and($ex['expected'])->toBe(40)
        ->and($ex['actual'])->toBe(0);
});

test('issuance records that differ from the ledger are an issuance_mismatch', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    rcIssue($b, 10, Carbon::parse('2026-06-12 09:00'), pivotQty: 7);

    $ex = rcService()->exceptions(rcPeriod('2026-06-10', '2026-06-15'))->firstWhere('kind', 'issuance_mismatch');

    expect($ex)->not->toBeNull()
        ->and($ex['batch_id'])->toBe($b->id)
        ->and($ex['expected'])->toBe(7)
        ->and($ex['actual'])->toBe(10);
});

test('a negative batch is reported as negative_stock', function () {
    $b = rcReceive($this->item, 5, Carbon::parse('2026-06-12 09:00'));
    $b->update(['remaining_qty' => -2]);

    expect(rcService()->exceptions(rcPeriod('2026-06-10', '2026-06-15'))->pluck('kind'))->toContain('negative_stock');
});

test('movement before the range is opening, not received', function () {
    rcCleanFlow();

    $row = rcRow('2026-06-13', '2026-06-15');

    expect($row['opening'])->toBe(150)
        ->and($row['received'])->toBe(0)
        ->and($row['issued'])->toBe(30)
        ->and($row['adjusted'])->toBe(-5)
        ->and($row['status'])->toBe('balanced');
});

test('a single day range only counts that day', function () {
    rcCleanFlow();

    $row = rcRow('2026-06-13', '2026-06-13');

    expect($row['opening'])->toBe(150)
        ->and($row['issued'])->toBe(30)
        ->and($row['received'])->toBe(0)
        ->and($row['adjusted'])->toBe(0)
        ->and($row['expected'])->toBe(120);

    $c = Livewire::withoutLazyLoading()->test(StockReconciliation::class)->set('range', 'custom')->set('from', '2026-06-13')->set('to', '2026-06-13');
    expect($c->instance()->period[0]->toDateString())->toBe('2026-06-13')
        ->and($c->instance()->period[1]->toDateString())->toBe('2026-06-13')
        ->and($c->instance()->rows->first()['issued'])->toBe(30);
});

test('a from date after the to date is swapped', function () {
    rcCleanFlow();

    $c = Livewire::withoutLazyLoading()->test(StockReconciliation::class)->set('range', 'custom')->set('from', '2026-06-14')->set('to', '2026-06-10');
    $period = $c->instance()->period;

    expect($period[0]->toDateString())->toBe('2026-06-10')
        ->and($period[1]->toDateString())->toBe('2026-06-14')
        ->and($c->instance()->rows->first()['received'])->toBe(50);
});

test('a past end date reads the ledger balance, not the batches', function () {
    rcCleanFlow(); // balance on 06-10 is 100; later moves are outside the range

    $row = rcRow('2026-06-01', '2026-06-10');

    expect($row['actual_source'])->toBe('ledger')
        ->and($row['actual'])->toBe(100)
        ->and($row['expected'])->toBe(100)
        ->and($row['variance'])->toBe(0)
        ->and($row['status'])->toBe('balanced');
});

test('category, search and status filters narrow the rows', function () {
    rcCleanFlow();

    $other = InventoryItems::factory()->create(['name' => 'Stapler', 'is_active' => true]);
    $ob = rcReceive($other, 20, Carbon::parse('2026-06-12 09:00'));
    $ob->decrement('remaining_qty', 3); // short by 3

    $cat = InventoryCategories::factory()->create(['name' => 'Office']);
    $other->inventoryCategory()->attach($cat->id);

    $names = fn (array $f) => rcService()->summary(rcPeriod('2026-06-10', '2026-06-15'), $f)->pluck('name')->all();

    expect($names([]))->toBe(['A4 Copy Paper', 'Stapler'])
        ->and($names(['category' => $cat->id]))->toBe(['Stapler'])
        ->and($names(['search' => 'copy']))->toBe(['A4 Copy Paper'])
        ->and($names(['status' => 'short']))->toBe(['Stapler'])
        ->and($names(['status' => 'balanced']))->toBe(['A4 Copy Paper'])
        ->and($names(['status' => 'overflow']))->toBe([])
        ->and($names(['status' => 'exceptions']))->toBe(['Stapler']);
});

test('the location filter limits the ledger and batches to that location', function () {
    rcCleanFlow();
    $other = StorageLocations::factory()->create(['name' => 'Annex']);
    $annexItem = InventoryItems::factory()->create(['name' => 'Stapler', 'is_active' => true]);
    rcReceive($annexItem, 20, Carbon::parse('2026-06-12 09:00'), $other);

    $rows = rcService()->summary(rcPeriod('2026-06-10', '2026-06-15'), ['location' => $other->id]);

    expect($rows->pluck('name')->all())->toBe(['Stapler'])
        ->and($rows->first()['received'])->toBe(20)
        ->and($rows->first()['actual'])->toBe(20)
        ->and($rows->first()['status'])->toBe('balanced');
});

test('a location filter with a past end date is unverified', function () {
    rcCleanFlow();

    $row = rcRow('2026-06-01', '2026-06-10', filters: ['location' => $this->location->id]);

    expect($row['status'])->toBe('unverified')
        ->and($row['actual'])->toBeNull()
        ->and($row['variance'])->toBeNull();
});

test('the page renders and is in the navigation config', function () {
    rcCleanFlow();

    $this->get(route('monitoring.stock-reconciliation'))
        ->assertOk()
        ->assertSee('Stock Reconciliation');

    expect(config('navigation.sections.monitoring.pages'))->toHaveKey('monitoring.stock-reconciliation');
});

test('rows break exceptions down by kind', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    rcIssue($b, 10, Carbon::parse('2026-06-12 09:00'), pivotQty: 7);
    $b->decrement('remaining_qty', 2);

    $row = rcRow('2026-06-10', '2026-06-15');

    expect($row['exception_kinds'])->toBe(['batch_unbalanced' => 1, 'issuance_mismatch' => 1])
        ->and($row['exception_count'])->toBe(2);
});

test('the exceptions column shows kind badges and the flyout lists the item exceptions', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    rcIssue($b, 10, Carbon::parse('2026-06-12 09:00'), pivotQty: 7);

    $other = InventoryItems::factory()->create(['name' => 'Ballpen', 'is_active' => true]);
    rcReceive($other, 5, Carbon::parse('2026-06-12 09:00'), withLedger: false);

    Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->assertSee('Issuance ×1')
        ->assertSee('Receipt ×1')
        ->assertSee('View transaction history')
        ->call('showExceptions', $this->item->id)
        ->assertSet('exceptionItem', $this->item->id)
        ->assertDispatched('modal-show', name: 'item-exceptions')
        ->tap(function ($c) {
            $listed = $c->instance()->itemExceptions;
            expect($listed)->not->toBeEmpty()
                ->and($listed->pluck('item_id')->unique()->all())->toBe([$this->item->id])
                ->and($listed->pluck('kind'))->toContain('issuance_mismatch');
        })
        ->assertSee('Exceptions — A4 Copy Paper');
});

test('clearFilters resets the filters', function () {
    Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->set('search', 'paper')->set('status', 'short')->set('range', '7')
        ->call('clearFilters')
        ->assertSet('search', '')->assertSet('status', 'all')->assertSet('range', '30');
});

test('exportPdf downloads a pdf', function () {
    rcCleanFlow();

    Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->call('exportPdf')
        ->assertFileDownloaded(contentType: 'application/pdf');
});

test('rendering and exporting never change stock or the ledger', function () {
    $b = rcCleanFlow();
    $b->decrement('remaining_qty', 3); // inconsistent on purpose; must not be "fixed"
    $before = rcSnapshot();

    $c = Livewire::withoutLazyLoading()->test(StockReconciliation::class);
    $c->call('exportPdf');
    $c->set('status', 'short')->set('range', 'ytd');
    $this->get(route('monitoring.stock-reconciliation'))->assertOk();

    expect(rcSnapshot())->toBe($before);
});

function rcManyItems(int $count = 19): void
{
    foreach (range(1, $count) as $i) {
        $item = InventoryItems::factory()->create(['name' => sprintf('Paged Item %02d', $i), 'is_active' => true]);
        rcReceive($item, 10, Carbon::parse('2026-06-12 09:00'));
    }
}

test('rows are paginated by perPage while the totals cover every item', function () {
    rcManyItems(19); // plus the beforeEach item = 20

    $c = Livewire::withoutLazyLoading()->test(StockReconciliation::class);
    $perPage = $c->instance()->perPage;
    $total = 20;

    expect($perPage)->toBeGreaterThan(0)
        ->and($c->instance()->pagedRows)->toHaveCount(min($perPage, $total))
        ->and($c->instance()->pagedRows->total())->toBe($total)
        ->and($c->instance()->totals['items'])->toBe($total);

    $c->call('gotoPage', 2);

    expect($c->instance()->pagedRows)->toHaveCount(max(0, min($perPage, $total - $perPage)))
        ->and($c->instance()->totals['items'])->toBe($total);

    // Past the last page the component never returns more rows than exist.
    $c->call('gotoPage', 99);
    expect($c->instance()->pagedRows->count())->toBeLessThanOrEqual($perPage)
        ->and($c->instance()->pagedRows->total())->toBe($total);
});

test('exportPdf on page two still downloads', function () {
    rcManyItems(19); // plus the beforeEach item = 20

    Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->call('gotoPage', 2)
        ->call('exportPdf')
        ->assertFileDownloaded(contentType: 'application/pdf');
});

test('changing a filter returns to page one', function () {
    rcManyItems(19); // plus the beforeEach item = 20

    $c = Livewire::withoutLazyLoading()->test(StockReconciliation::class)->call('gotoPage', 2);
    expect($c->instance()->getPage())->toBe(2);

    $c->set('search', 'Paged');
    expect($c->instance()->getPage())->toBe(1);

    $c->call('gotoPage', 2)->call('clearFilters');
    expect($c->instance()->getPage())->toBe(1);
});

test('pagination controls only appear with more than one page of rows', function () {
    rcManyItems(19); // plus the beforeEach item = 20

    Livewire::withoutLazyLoading()->test(StockReconciliation::class)->assertSeeHtml('wire:click="nextPage');

    Livewire::withoutLazyLoading()->test(StockReconciliation::class)->set('search', 'Paged Item 01')->assertDontSeeHtml('wire:click="nextPage');
});

/**
 * Every exception for the period in a stable, comparable shape.
 *
 * @return list<array{kind: string, item_id: int, batch_id: ?int, transaction_id: ?int, expected: int, actual: int, difference: int, occurred_at: string}>
 */
function rcExceptionList(string $from, string $to, array $filters = []): array
{
    return rcService()->exceptions(rcPeriod($from, $to), $filters)
        ->map(fn (array $e) => [
            'kind' => $e['kind'],
            'item_id' => (int) $e['item_id'],
            'batch_id' => $e['batch_id'] === null ? null : (int) $e['batch_id'],
            'transaction_id' => $e['transaction_id'] === null ? null : (int) $e['transaction_id'],
            'expected' => $e['expected'],
            'actual' => $e['actual'],
            'difference' => $e['difference'],
            'occurred_at' => $e['occurred_at']->format('Y-m-d H:i'),
        ])
        ->values()
        ->all();
}

test('a wrong balance just before the period flags the first row inside it', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    $issue = rcIssue($b, 30, Carbon::parse('2026-06-05 09:00'));
    $issue->forceFill(['balance_after' => 60])->saveQuietly(); // should be 70, and sits before the period
    $adjust = rcAdjust($b, -5, Carbon::parse('2026-06-12 09:00'));

    expect(rcExceptionList('2026-06-10', '2026-06-15'))->toBe([
        ['kind' => 'balance_break', 'item_id' => $this->item->id, 'batch_id' => $b->id, 'transaction_id' => $adjust->id,
            'expected' => 55, 'actual' => 65, 'difference' => 10, 'occurred_at' => '2026-06-12 09:00'],
    ]);

    expect(rcRow('2026-06-10', '2026-06-15'))->toMatchArray([
        'opening' => 70, 'adjusted' => -5, 'expected' => 65, 'actual' => 65, 'status' => 'balanced', 'exception_count' => 1,
    ]);
});

test('an item with no ledger rows before the period starts from zero', function () {
    $fresh = rcReceive($this->item, 40, Carbon::parse('2026-06-12 09:00'));
    $receipt = InventoryTransactions::where('batch_id', $fresh->id)->sole();
    $receipt->forceFill(['balance_after' => 45])->saveQuietly(); // should be 0 + 40

    // A second item with history before the period must not leak into the first.
    $older = InventoryItems::factory()->create(['name' => 'Stapler', 'is_active' => true]);
    $olderBatch = rcReceive($older, 100, Carbon::parse('2026-06-01 09:00'));
    rcIssue($olderBatch, 10, Carbon::parse('2026-06-12 10:00'));

    expect(rcExceptionList('2026-06-10', '2026-06-15'))->toBe([
        ['kind' => 'balance_break', 'item_id' => $this->item->id, 'batch_id' => $fresh->id, 'transaction_id' => $receipt->id,
            'expected' => 40, 'actual' => 45, 'difference' => 5, 'occurred_at' => '2026-06-12 09:00'],
    ]);
});

test('the location filter keeps only the breaks on that location while chaining the whole ledger', function () {
    $annex = StorageLocations::factory()->create(['name' => 'Annex']);
    $main = rcReceive($this->item, 100, Carbon::parse('2026-06-01 09:00'));
    $annexBatch = rcReceive($this->item, 50, Carbon::parse('2026-06-11 09:00'), $annex);
    $annexReceipt = InventoryTransactions::where('batch_id', $annexBatch->id)->sole();
    $annexReceipt->forceFill(['balance_after' => 140])->saveQuietly(); // should be 150
    $mainIssue = rcIssue($main, 10, Carbon::parse('2026-06-12 09:00')); // 140, but 140 - 10 = 130 is expected

    $annexBreak = ['kind' => 'balance_break', 'item_id' => $this->item->id, 'batch_id' => $annexBatch->id, 'transaction_id' => $annexReceipt->id,
        'expected' => 150, 'actual' => 140, 'difference' => -10, 'occurred_at' => '2026-06-11 09:00'];
    $mainBreak = ['kind' => 'balance_break', 'item_id' => $this->item->id, 'batch_id' => $main->id, 'transaction_id' => $mainIssue->id,
        'expected' => 130, 'actual' => 140, 'difference' => 10, 'occurred_at' => '2026-06-12 09:00'];

    expect(rcExceptionList('2026-06-10', '2026-06-15', ['location' => $annex->id]))->toBe([$annexBreak])
        ->and(rcExceptionList('2026-06-10', '2026-06-15', ['location' => $this->location->id]))->toBe([$mainBreak])
        ->and(rcExceptionList('2026-06-10', '2026-06-15'))->toBe([$annexBreak, $mainBreak]);

    expect(rcRow('2026-06-10', '2026-06-15', filters: ['location' => $annex->id]))->toMatchArray([
        'opening' => 0, 'received' => 50, 'expected' => 50, 'actual' => 50, 'status' => 'balanced', 'exception_count' => 1,
    ]);
});

test('a batch received before the period without a Receipt row is flagged once it moves in the period', function () {
    $moved = rcReceive($this->item, 40, Carbon::parse('2026-06-01 09:00'), withLedger: false);
    rcReceive($this->item, 25, Carbon::parse('2026-06-02 09:00'), withLedger: false); // untouched in the period
    $issue = rcIssue($moved, 10, Carbon::parse('2026-06-12 09:00'));

    expect(rcExceptionList('2026-06-10', '2026-06-15'))->toBe([
        ['kind' => 'receipt_mismatch', 'item_id' => $this->item->id, 'batch_id' => $moved->id, 'transaction_id' => null,
            'expected' => 40, 'actual' => 0, 'difference' => -40, 'occurred_at' => '2026-06-01 09:00'],
        ['kind' => 'balance_break', 'item_id' => $this->item->id, 'batch_id' => $moved->id, 'transaction_id' => $issue->id,
            'expected' => -10, 'actual' => 55, 'difference' => 65, 'occurred_at' => '2026-06-12 09:00'],
    ]);
});

test('issuance records and ledger issuances that disagree in either direction are flagged per batch', function () {
    $pivotOnly = rcReceive($this->item, 50, Carbon::parse('2026-06-11 09:00'));
    $ledgerOnly = rcReceive($this->item, 30, Carbon::parse('2026-06-11 10:00'));

    // A recorded draw of 5 whose ledger row is missing (stock left untouched).
    $orphan = rcIssue($pivotOnly, 5, Carbon::parse('2026-06-12 09:00'));
    DB::table('inventory_transactions')->where('id', $orphan->id)->delete();
    DB::table('inventory_batches')->where('id', $pivotOnly->id)->increment('remaining_qty', 5);

    // A ledger issuance of 10 whose issuance record says nothing was drawn.
    rcIssue($ledgerOnly, 10, Carbon::parse('2026-06-13 09:00'), pivotQty: 0);

    $issuanceMismatches = collect(rcExceptionList('2026-06-10', '2026-06-15'))->where('kind', 'issuance_mismatch')->values()->all();

    expect($issuanceMismatches)->toBe([
        ['kind' => 'issuance_mismatch', 'item_id' => $this->item->id, 'batch_id' => $pivotOnly->id, 'transaction_id' => null,
            'expected' => 5, 'actual' => 0, 'difference' => -5, 'occurred_at' => '2026-06-11 09:00'],
        ['kind' => 'issuance_mismatch', 'item_id' => $this->item->id, 'batch_id' => $ledgerOnly->id, 'transaction_id' => null,
            'expected' => 0, 'actual' => 10, 'difference' => 10, 'occurred_at' => '2026-06-11 10:00'],
    ]);
});

test('the reconciliation runs the same number of queries however many batches there are', function () {
    $items = InventoryItems::factory()->count(10)->create(['is_active' => true]);
    $grow = function (int $batchesPerItem) use ($items): void {
        foreach ($items as $item) {
            foreach (range(1, $batchesPerItem) as $n) {
                $batch = rcReceive($item, 10, Carbon::parse('2026-06-11 09:00')->addMinutes($n));
                rcIssue($batch, 2, Carbon::parse('2026-06-12 09:00')->addMinutes($n));
            }
        }
    };

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    // The service rather than a full page render: the page's Livewire snapshot
    // of 600+ batches is large enough to exhaust memory late in the suite.
    $queriesForPage = function () use (&$queries): int {
        Cache::flush();
        $queries = 0;

        $service = rcService();
        $rows = $service->summary(rcPeriod('2026-06-10', '2026-06-15'));
        $service->exceptions(rcPeriod('2026-06-10', '2026-06-15'));

        expect($rows)->toHaveCount(11);

        return $queries;
    };

    $grow(20);
    $small = $queriesForPage();
    $grow(40);
    $large = $queriesForPage();

    expect(InventoryBatches::count())->toBeGreaterThan(500)
        ->and($large)->toBe($small);
});

test('a past period reads the last balance at or before its end, breaking a timestamp tie by id', function () {
    $b = rcReceive($this->item, 100, Carbon::parse('2026-06-12 09:00'));
    $sameMoment = rcIssue($b, 30, Carbon::parse('2026-06-12 09:00')); // same created_at, later id: balance 70
    rcAdjust($b, -5, Carbon::parse('2026-06-14 09:00')); // after the period: ignored

    $row = rcRow('2026-06-10', '2026-06-12');

    expect($sameMoment->balance_after)->toBe(70)
        ->and($row)->toMatchArray([
            'received' => 100,
            'issued' => 30,
            'expected' => 70,
            'recorded' => 70,
            'actual' => 70,
            'actual_source' => 'ledger',
            'variance' => 0,
            'status' => 'balanced',
        ]);
});

/**
 * $count active items, each with one batch received in the period but no
 * Receipt row: exactly one receipt_mismatch exception per item.
 */
function rcUnreceivedItems(int $count): void
{
    foreach (range(1, $count) as $n) {
        $item = InventoryItems::factory()->create(['name' => sprintf('Unreceived %02d', $n), 'is_active' => true]);
        rcReceive($item, 5, Carbon::parse('2026-06-12 09:00')->addMinutes($n), withLedger: false);
    }
}

function rcPage(): Testable
{
    return Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->set('range', 'custom')
        ->set('from', '2026-06-10')
        ->set('to', '2026-06-15');
}

test('the exceptions card pages through the exceptions while the stat counts them all', function () {
    rcUnreceivedItems(30);

    $page = rcPage();
    $first = $page->instance()->pagedExceptions;

    expect($first)->toHaveCount(StockReconciliation::EXCEPTIONS_PER_PAGE)
        ->and($first->total())->toBe(30)
        ->and($page->instance()->exceptions)->toHaveCount(30);

    $page->call('setPage', 2, StockReconciliation::EXCEPTIONS_PAGE_NAME);
    $second = $page->instance()->pagedExceptions;

    expect($second)->toHaveCount(5)
        ->and($second->currentPage())->toBe(2)
        ->and($page->instance()->exceptions)->toHaveCount(30)
        ->and(collect($first->items())->merge($second->items())->pluck('batch_id')->unique())->toHaveCount(30);
});

test('changing a filter or the period returns the exceptions card to page one', function () {
    rcUnreceivedItems(30);
    $page = rcPage();
    $exceptionsPage = fn () => $page->instance()->getPage(StockReconciliation::EXCEPTIONS_PAGE_NAME);

    $page->call('setPage', 2, StockReconciliation::EXCEPTIONS_PAGE_NAME);
    expect($exceptionsPage())->toBe(2);

    $page->set('search', 'Unreceived');
    expect($exceptionsPage())->toBe(1);

    $page->call('setPage', 2, StockReconciliation::EXCEPTIONS_PAGE_NAME)->set('to', '2026-06-14');
    expect($exceptionsPage())->toBe(1);
});

test('the items table and the exceptions card page independently', function () {
    rcUnreceivedItems(30); // plus the beforeEach item: 31 rows over 4 pages
    $page = rcPage();
    $pages = fn () => [$page->instance()->getPage(), $page->instance()->getPage(StockReconciliation::EXCEPTIONS_PAGE_NAME)];

    $page->call('setPage', 3)->call('setPage', 2, StockReconciliation::EXCEPTIONS_PAGE_NAME);
    expect($pages())->toBe([3, 2])
        ->and($page->instance()->pagedRows->currentPage())->toBe(3)
        ->and($page->instance()->pagedExceptions->currentPage())->toBe(2);

    $page->call('setPage', 1);
    expect($pages())->toBe([1, 2]);
});
