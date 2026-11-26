<?php

use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Enums\StockStatus;
use App\Livewire\Monitoring\StockDetail;
use App\Livewire\Monitoring\StockMonitoring;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->category = InventoryCategories::factory()->create(['name' => 'Medical', 'is_active' => true]);

    $this->receipt = StockReceipts::factory()->create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->user->id,
    ]);
    $this->location = StorageLocations::factory()->create();
});

function stockItem(array $attributes = []): InventoryItems
{
    return InventoryItems::factory()->create([
        'minimum_stock' => 5,
        'reorder_level' => 20,
        'is_active' => true,
        'requires_expiry' => false,
        ...$attributes,
    ]);
}

function stockBatch(InventoryItems $item, int $qty, array $overrides = []): InventoryBatches
{
    return InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => test()->receipt->id,
        'storage_location_id' => test()->location->id,
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'received_at' => now()->subDays(30),
        'expiration_date' => null,
        ...$overrides,
    ]);
}

function stockLedger(InventoryBatches $batch, InventoryTransactionType $type, int $qty, CarbonInterface $at): void
{
    $t = new InventoryTransactions([
        'inventory_item_id' => $batch->inventory_item_id,
        'batch_id' => $batch->id,
        'user_id' => test()->user->id,
        'type' => $type,
        'quantity' => $qty,
        'balance_after' => 0,
        'reference_type' => 'test',
        'reference_id' => 1,
    ]);
    $t->created_at = $at;
    $t->save();
}

function stockCommitment(InventoryItems $item, int $outstanding): void
{
    $requisition = Requisitions::create([
        'request_number' => 'REQ-'.fake()->unique()->numerify('####'),
        'user_id' => test()->user->id,
        'status' => RequisitionStatus::Approved,
        'purpose' => 'Test',
        'submitted_at' => now(),
    ]);

    $requisition->items()->create([
        'inventory_item_id' => $item->id,
        'requested_qty' => $outstanding,
        'approved_qty' => $outstanding,
        'released_qty' => 0,
        'outstanding_qty' => $outstanding,
    ]);
}

/** The component mounted and rendered directly, skipping the #[Defer] placeholder. */
function stockMonitoring(): Testable
{
    return Livewire::withoutLazyLoading()->test(StockMonitoring::class);
}

/**
 * A row's drill-down, rendered directly (skipping its lazy placeholder).
 *
 * @return array<string, mixed>|null
 */
function stockDetail(InventoryItems $item): ?array
{
    return Livewire::withoutLazyLoading()->test(StockDetail::class, ['itemId' => $item->id])->instance()->detail;
}

/**
 * Replays the browser's deferred load. The placeholder is rendered for a page URL
 * with the given query string (the #[Url] values are read then and travel in the
 * placeholder's snapshot), then the `__lazyLoad` call from its x-init mounts and
 * renders the real component.
 *
 * @param  array<string, mixed>  $query
 */
function stockDeferredLoad(array $query): Testable
{
    $page = Livewire::withQueryParams($query)->test(StockMonitoring::class)
        ->assertSee('Loading stock monitoring');

    // The quotes around the encoded mount params may be HTML-escaped in the attribute.
    preg_match('/__lazyLoad\((?:\'|&#0?39;)([A-Za-z0-9+\/=]+)/', $page->html(), $match);

    return $page->call('__lazyLoad', $match[1]);
}

test('the stock monitoring page renders', function () {
    stockBatch(stockItem(['name' => 'Copy Paper']), 50);

    $this->get(route('monitoring.stock-monitoring'))
        ->assertOk()
        ->assertSee('Stock Monitoring')
        ->assertSee('Loading stock monitoring');

    stockMonitoring()->assertSee('Copy Paper');
});

test('the initial page renders the placeholder without running the stock queries', function () {
    $item = stockItem(['name' => 'Copy Paper']);
    $batch = stockBatch($item, 50);
    stockLedger($batch, InventoryTransactionType::Receipt, 50, now()->subDay());

    // rows() reads items, batches and the ledger. The deferred shell must not
    // touch any of them; they run only once the browser asks for __lazyLoad.
    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $this->get(route('monitoring.stock-monitoring'));
    $queries = array_column(DB::getQueryLog(), 'query');
    DB::disableQueryLog();

    $response->assertOk()
        ->assertSee('__lazyLoad', false)
        ->assertDontSee('Copy Paper');

    expect(preg_grep('/\b(inventory_items|inventory_batches|inventory_transactions)\b/', $queries))->toBeEmpty();
});

test('the deferred load applies the URL filters from the page address', function () {
    $paper = stockItem(['name' => 'Copy Paper']);
    stockBatch($paper, 50);
    $gauze = stockItem(['name' => 'Sterile Gauze']);
    stockBatch($gauze, 3, ['batch_number' => 'BATCH-GAUZE-1']);

    stockDeferredLoad(['status' => StockStatus::Critical->value, 'sort' => 'name', 'expanded' => $gauze->id])
        ->assertSet('status', 'critical')
        ->assertSet('sort', 'name')
        ->assertSet('expanded', $gauze->id)
        ->assertDontSee('Loading stock monitoring')
        ->assertSee('Sterile Gauze')
        ->assertSeeHtml('detail-'.$gauze->id)
        ->assertSeeLivewire('monitoring.stock-detail')
        ->assertSee('Loading item details')
        ->assertDontSee('BATCH-GAUZE-1') // the row's detail loads lazily, on its own
        ->assertDontSee('Copy Paper');

    Livewire::withoutLazyLoading()->test(StockDetail::class, ['itemId' => $gauze->id])
        ->assertSee('BATCH-GAUZE-1');
});

test('items are classified by usable stock and expired stock does not count', function () {
    $out = stockItem();
    $critical = stockItem();
    stockBatch($critical, 3);
    $reorder = stockItem();
    stockBatch($reorder, 15);
    $healthy = stockItem();
    stockBatch($healthy, 50);
    stockBatch($out, 40, ['expiration_date' => today()->subDay()]);   // expired: not usable
    stockItem(['is_active' => false]);

    $component = stockMonitoring()->instance();

    expect($component->counts)->toBe(['out' => 1, 'critical' => 1, 'reorder' => 1, 'healthy' => 1])
        ->and($component->rows->firstWhere('id', $out->id)->expired)->toBe(40)
        ->and($component->items->pluck('id')->all())->toBe([$out->id, $critical->id, $reorder->id, $healthy->id]);
});

test('committed stock is subtracted to give free stock and days of cover', function () {
    $item = stockItem();
    $batch = stockBatch($item, 100);
    stockCommitment($item, 30);

    // 180 issued over the 90-day window = 2 per day.
    stockLedger($batch, InventoryTransactionType::Issuance, -180, now()->subDays(10));
    stockLedger($batch, InventoryTransactionType::Issuance, -999, now()->subDays(StockMonitoring::USAGE_DAYS + 5));

    $row = stockMonitoring()->instance()->rows->firstWhere('id', $item->id);

    expect($row->available)->toBe(100)
        ->and($row->committed)->toBe(30)
        ->and($row->free)->toBe(70)
        ->and($row->daily_usage)->toBe(2.0)
        ->and($row->cover_days)->toBe(35);
});

test('items with no recent usage have no days of cover', function () {
    $item = stockItem();
    stockBatch($item, 10);

    expect(stockMonitoring()->instance()->rows->firstWhere('id', $item->id)->cover_days)->toBeNull();
});

test('search, category and status filters narrow the list', function () {
    $paper = stockItem(['name' => 'Copy Paper', 'item_code' => 'INV-1000']);
    stockBatch($paper, 100);
    $gauze = stockItem(['name' => 'Sterile Gauze']);

    // The factory attaches random active categories; set them explicitly.
    $paper->inventoryCategory()->sync([]);
    $gauze->inventoryCategory()->sync([$this->category->id]);

    $component = stockMonitoring();

    $component->set('search', 'inv-1000');
    expect($component->instance()->items->pluck('id')->all())->toBe([$paper->id]);

    $component->set('search', '')->set('category', (string) $this->category->id);
    expect($component->instance()->items->pluck('id')->all())->toBe([$gauze->id]);

    $component->set('category', '')->set('status', 'attention');
    expect($component->instance()->items->pluck('id')->all())->toBe([$gauze->id]);

    $component->call('filterStatus', StockStatus::Healthy->value);
    expect($component->instance()->items->pluck('id')->all())->toBe([$paper->id]);

    $component->set('status', 'nonsense')->assertSet('status', '');
});

test('the drill-down lists batches in FEFO order with reservations and expired batches last', function () {
    $item = stockItem(['requires_expiry' => true]);
    $late = stockBatch($item, 10, ['expiration_date' => today()->addDays(200)]);
    $soon = stockBatch($item, 6, ['expiration_date' => today()->addDays(20)]);
    $expired = stockBatch($item, 4, ['expiration_date' => today()->subDays(3)]);
    stockCommitment($item, 8);

    $detail = stockDetail($item);

    $rows = collect($detail['batches']);

    expect($detail['method'])->toBe('FEFO')
        ->and($rows->map(fn ($r) => $r['batch']->id)->all())->toBe([$soon->id, $late->id, $expired->id])
        ->and($rows->pluck('reserved')->all())->toBe([6, 2, 0])
        ->and($rows->pluck('expired')->all())->toBe([false, false, true]);
});

test('the balance trend is rebuilt from today backwards', function () {
    $item = stockItem();
    $batch = stockBatch($item, 50);
    stockLedger($batch, InventoryTransactionType::Receipt, 50, now()->subDays(10));
    $batch->update(['remaining_qty' => 40]);
    stockLedger($batch, InventoryTransactionType::Issuance, -10, now()->subDays(2));

    $detail = stockDetail($item);
    $trend = collect($detail['trend'])->keyBy('date');

    expect($detail['trend'])->toHaveCount(StockMonitoring::TREND_DAYS)
        ->and($trend[today()->toDateString()]['balance'])->toBe(40)
        ->and($trend[today()->subDays(5)->toDateString()]['balance'])->toBe(50)
        ->and($trend[today()->subDays(15)->toDateString()]['balance'])->toBe(0)
        ->and($detail['movement'])->toBe(['received' => 50, 'issued' => 10, 'adjusted' => 0]);
});

test('the expanded row is kept in the page address and can be cleared', function () {
    // Opening and closing a row happens in the browser now; the server only
    // keeps the open row in the URL-bound `expanded` property.
    $item = stockItem();

    Livewire::withQueryParams(['expanded' => $item->id])->withoutLazyLoading()->test(StockMonitoring::class)
        ->assertSet('expanded', $item->id)
        ->set('expanded', null)
        ->assertSet('expanded', null);

    stockMonitoring()
        ->set('expanded', $item->id)
        ->assertSet('expanded', $item->id);
});

test('the drill-down reserves FIFO stock oldest first and never more than a batch holds', function () {
    $item = stockItem();
    $oldest = stockBatch($item, 3, ['received_at' => now()->subDays(30)]);
    $middle = stockBatch($item, 4, ['received_at' => now()->subDays(20)]);
    $newest = stockBatch($item, 5, ['received_at' => now()->subDays(10)]);
    stockCommitment($item, 9);
    stockCommitment($item, 6);

    $rows = collect(stockDetail($item)['batches']);

    expect($rows->map(fn ($r) => $r['batch']->id)->all())->toBe([$oldest->id, $middle->id, $newest->id])
        ->and($rows->pluck('reserved')->all())->toBe([3, 4, 5])
        ->and($rows->pluck('order')->all())->toBe([1, 2, 3]);
});

test('a row detail shows a placeholder until it loads', function () {
    $item = stockItem();
    stockBatch($item, 5, ['batch_number' => 'BATCH-LAZY-1']);

    // Mounted the way the list mounts it: <livewire:monitoring.stock-detail lazy />.
    Livewire::test(StockDetail::class, ['itemId' => $item->id, 'lazy' => true])
        ->assertSee('Loading item details')
        ->assertDontSee('BATCH-LAZY-1');
});

test('a row detail cannot be pointed at another item from the browser', function () {
    $item = stockItem();
    $other = stockItem();

    expect(fn () => Livewire::withoutLazyLoading()->test(StockDetail::class, ['itemId' => $item->id])->set('itemId', $other->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('a row detail for an unknown or inactive item shows that it is unavailable', function (Closure $itemId) {
    $component = Livewire::withoutLazyLoading()->test(StockDetail::class, ['itemId' => $itemId()])
        ->assertSee('This item is no longer available.');

    expect($component->instance()->detail)->toBeNull();
})->with([
    // Passed through as closures (the parameter is typed Closure), built when called.
    'unknown item' => fn () => 999_999,
    'inactive item' => fn () => stockItem(['is_active' => false])->id,
]);
