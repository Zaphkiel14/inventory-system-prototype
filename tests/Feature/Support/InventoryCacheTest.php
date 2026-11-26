<?php

use App\Enums\AdjustmentType;
use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\AdjustmentCreateForm;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Monitoring\StockMonitoring;
use App\Livewire\Monitoring\StockReconciliation;
use App\Livewire\Monitoring\TransactionHistory;
use App\Livewire\Portal\RequestForm;
use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Support\InventoryCache;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\FileStore;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * Cache invalidation against a real file store: the array store keeps PHP
 * values in memory and would hide serialization problems (config/cache.php
 * refuses to unserialize objects).
 */

beforeEach(function () {
    $this->cachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inventory-cache-test-'.Str::random(12);
    config([
        'cache.stores.file.path' => $this->cachePath,
        'cache.stores.file.lock_path' => $this->cachePath.DIRECTORY_SEPARATOR.'locks',
    ]);
    Cache::forgetDriver('file');
    Cache::setDefaultDriver('file');

    $this->travelTo(now()->setDate(2026, 6, 15)->setTime(10, 0));
    Notification::fake();
    Storage::fake('public');

    $this->user = User::factory()->create(['name' => 'Cache Custodian']);
    $this->actingAs($this->user);

    $this->category = InventoryCategories::factory()->create(['name' => 'Medical', 'is_active' => true]);
    $this->location = StorageLocations::factory()->create(['name' => 'Main Storage Room']);
    $this->supplier = Suppliers::factory()->create(['name' => 'Acme Supply', 'is_active' => true]);
    $this->item = InventoryItems::factory()->create([
        'name' => 'Gauze Roll', 'is_active' => true, 'requires_expiry' => false, 'minimum_stock' => 10, 'reorder_level' => 50,
    ]);
    $this->batch = cacheStock($this->item, 20);
});

afterEach(function () {
    File::deleteDirectory($this->cachePath);
});

function cacheStock(InventoryItems $item, int $quantity): InventoryBatches
{
    $receipt = StockReceipts::factory()->complete()->receivedOn(now()->subDays(3))->create([
        'supplier_id' => test()->supplier->id,
        'received_by' => test()->user->id,
    ]);
    $batch = InventoryBatches::factory()->create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => $receipt->id,
        'storage_location_id' => test()->location->id,
        'received_qty' => $quantity,
        'remaining_qty' => $quantity,
        'received_at' => now()->subDays(3),
        'expiration_date' => null,
    ]);
    InventoryTransactions::create([
        'inventory_item_id' => $item->id,
        'batch_id' => $batch->id,
        'user_id' => test()->user->id,
        'type' => InventoryTransactionType::Receipt,
        'quantity' => $quantity,
        'balance_after' => $quantity,
        'reference_type' => StockReceipts::class,
        'reference_id' => $receipt->id,
    ]);

    return $batch;
}

function cacheIsland(Testable $page, string $island): string
{
    $page->update(calls: [[
        'method' => '__lazyLoadIsland',
        'params' => [],
        'path' => '',
        'metadata' => ['island' => ['name' => $island, 'mode' => 'morph']],
    ]]);

    return implode('', $page->effects['islandFragments'] ?? []);
}

/**
 * Renders StockMonitoring, the Dashboard lists island and Reconciliation, and
 * returns what each shows for the item.
 *
 * @return array{monitoring: ?int, dashboard: ?int, dashboard_html: string, reconciliation: ?int, reconciliation_name: ?string}
 */
function cacheObserve(InventoryItems $item): array
{
    $figures = Livewire::withoutLazyLoading()->test(StockMonitoring::class)->instance()->figures;

    $dashboard = Livewire::test(Dashboard::class);
    $html = cacheIsland($dashboard, 'lists');
    $low = $dashboard->instance()->lowStock->firstWhere('id', $item->id);

    $row = Livewire::withoutLazyLoading()->test(StockReconciliation::class)->instance()->rows->firstWhere('item_id', $item->id);

    return [
        'monitoring' => $figures[$item->id]['available_qty'] ?? null,
        'dashboard' => $low === null ? null : (int) $low->available_qty,
        'dashboard_html' => $html,
        'reconciliation' => $row['actual'] ?? null,
        'reconciliation_name' => $row['name'] ?? null,
    ];
}

/** @return array{monitoring: ?int, dashboard: ?int, reconciliation: ?int} */
function cacheQuantities(InventoryItems $item): array
{
    return collect(cacheObserve($item))->only(['monitoring', 'dashboard', 'reconciliation'])->all();
}

function cacheRelease(InventoryItems $item, int $quantity): void
{
    cacheOpenRelease([[$item, $quantity]])->call('release')->assertHasNoErrors();
}

/**
 * An approved requisition for the lines, opened for release (not yet released).
 *
 * @param  list<array{0: InventoryItems, 1: int}>  $lines
 */
function cacheOpenRelease(array $lines): Testable
{
    $requisition = Requisitions::create([
        'request_number' => 'REQ-CACHE-'.Str::random(6),
        'user_id' => test()->user->id,
        'status' => RequisitionStatus::Approved,
        'purpose' => 'Ward supplies',
        'submitted_at' => now(),
    ]);

    foreach ($lines as [$item, $quantity]) {
        $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'requested_qty' => $quantity,
            'available_qty' => $quantity,
            'approved_qty' => $quantity,
            'released_qty' => 0,
            'outstanding_qty' => $quantity,
        ]);
    }

    return Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
}

/**
 * How many times the callback writes the ledger version to the cache store.
 */
function ledgerVersionWrites(callable $callback): int
{
    $writes = 0;
    Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$writes): void {
        if ($event->key === InventoryCache::LEDGER_VERSION) {
            $writes++;
        }
    });

    $callback();

    return $writes;
}

/* -------------------------------------------------------------------------- */
/* The store itself */
/* -------------------------------------------------------------------------- */

it('serves cached stock figures from the file store until the ledger version changes', function () {
    expect(Cache::getStore())->toBeInstanceOf(FileStore::class)
        ->and(cacheQuantities($this->item))->toBe(['monitoring' => 20, 'dashboard' => 20, 'reconciliation' => 20])
        ->and(File::allFiles($this->cachePath))->not->toBeEmpty();

    // A raw write skips the model hooks, so the cached figures stay as they were.
    DB::table('inventory_batches')->where('id', $this->batch->id)->update(['remaining_qty' => 12]);
    expect(cacheQuantities($this->item))->toBe(['monitoring' => 20, 'dashboard' => 20, 'reconciliation' => 20]);

    InventoryCache::bumpLedger();
    expect(cacheQuantities($this->item))->toBe(['monitoring' => 12, 'dashboard' => 12, 'reconciliation' => 12]);
});

it('reads lookup lists back from the file store as models without querying', function () {
    InventoryCache::categories();

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    $categories = InventoryCache::categories();

    expect($queries)->toBe(0)
        ->and($categories->first())->toBeInstanceOf(InventoryCategories::class)
        ->and($categories->pluck('name')->all())->toBe(['Medical'])
        ->and($categories->first()->getAttributes())->toEqual(InventoryCategories::query()->first()->getAttributes());
});

/* -------------------------------------------------------------------------- */
/* Stock figures follow every kind of change */
/* -------------------------------------------------------------------------- */

it('shows a new receipt on every cached page', function () {
    expect(cacheQuantities($this->item))->toBe(['monitoring' => 20, 'dashboard' => 20, 'reconciliation' => 20]);

    Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', $this->supplier->id)
        ->set('form.reference_number', 'DR-CACHE-001')
        ->set('form.items.0.inventory_item_id', $this->item->id)
        ->set('form.items.0.storage_location_id', $this->location->id)
        ->set('form.items.0.quantity', 5)
        ->set('form.items.0.unit_cost', 3)
        ->set('attachments', [UploadedFile::fake()->create('dr.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasNoErrors();

    expect(cacheQuantities($this->item))->toBe(['monitoring' => 25, 'dashboard' => 25, 'reconciliation' => 25]);
});

it('shows an adjustment on every cached page', function () {
    expect(cacheQuantities($this->item))->toBe(['monitoring' => 20, 'dashboard' => 20, 'reconciliation' => 20]);

    Livewire::test(AdjustmentCreateForm::class)
        ->set('form.type', AdjustmentType::Loss->value)
        ->set('form.batch_id', $this->batch->id)
        ->set('form.quantity', 3)
        ->set('form.reason', 'Water damage')
        ->set('form.requested_by', 'Ana Requester')
        ->set('form.approved_by', 'Mark Supervisor')
        ->call('save')
        ->assertHasNoErrors();

    expect(cacheQuantities($this->item))->toBe(['monitoring' => 17, 'dashboard' => 17, 'reconciliation' => 17]);
});

it('shows a release on every cached page', function () {
    expect(cacheQuantities($this->item))->toBe(['monitoring' => 20, 'dashboard' => 20, 'reconciliation' => 20]);

    cacheRelease($this->item, 4);

    expect(cacheQuantities($this->item))->toBe(['monitoring' => 16, 'dashboard' => 16, 'reconciliation' => 16]);
});

it('shows a renamed item on every cached page', function () {
    $before = cacheObserve($this->item);
    expect($before['dashboard_html'])->toContain('Gauze Roll')
        ->and($before['reconciliation_name'])->toBe('Gauze Roll');

    $this->item->update(['name' => 'Sterile Gauze Roll']);

    $after = cacheObserve($this->item);
    expect($after['dashboard_html'])->toContain('Sterile Gauze Roll')
        ->and($after['reconciliation_name'])->toBe('Sterile Gauze Roll');
    Livewire::withoutLazyLoading()->test(StockMonitoring::class)->assertSee('Sterile Gauze Roll');
});

it('shows a batch moved to another location in the location-filtered reconciliation', function () {
    $annex = StorageLocations::factory()->create(['name' => 'Annex']);
    $annexRows = fn () => Livewire::withoutLazyLoading()->test(StockReconciliation::class)
        ->set('location', (string) $annex->id)
        ->instance()
        ->rows
        ->pluck('actual', 'item_id')
        ->all();

    expect($annexRows())->toBe([]);

    $this->batch->update(['storage_location_id' => $annex->id]);

    expect($annexRows())->toBe([$this->item->id => 20]);
});

/* -------------------------------------------------------------------------- */
/* Version bumps and transactions */
/* -------------------------------------------------------------------------- */

it('does not bump the ledger version when the transaction rolls back', function () {
    $version = InventoryCache::ledgerVersion();

    try {
        DB::transaction(function () {
            $this->batch->decrement('remaining_qty', 5);
            $this->batch->save();
            InventoryTransactions::create([
                'inventory_item_id' => $this->item->id,
                'batch_id' => $this->batch->id,
                'user_id' => $this->user->id,
                'type' => InventoryTransactionType::Adjustment,
                'quantity' => -5,
                'balance_after' => 15,
                'reference_type' => 'test',
                'reference_id' => 1,
            ]);

            throw new RuntimeException('roll back');
        });
    } catch (RuntimeException) {
        // expected
    }

    expect(InventoryCache::ledgerVersion())->toBe($version);
});

it('bumps the ledger version once the transaction commits', function () {
    $version = InventoryCache::ledgerVersion();

    DB::transaction(function () use ($version) {
        $this->item->update(['minimum_stock' => 12]);

        expect(InventoryCache::ledgerVersion())->toBe($version);
    });

    expect(InventoryCache::ledgerVersion())->not->toBe($version);
});

/* -------------------------------------------------------------------------- */
/* Lookup lists */
/* -------------------------------------------------------------------------- */

it('shows renamed categories, locations and suppliers in the selects on the next read', function () {
    $stocks = fn () => Livewire::withoutLazyLoading()->test(Stocks::class)->instance()->categories;
    $receiving = fn () => Livewire::test(ReceivingCreateForm::class)->instance();
    $reconciliation = fn () => Livewire::withoutLazyLoading()->test(StockReconciliation::class)->instance();

    expect($stocks())->toBe([$this->category->id => 'Medical'])
        ->and($receiving()->suppliers->pluck('name')->all())->toBe(['Acme Supply'])
        ->and($receiving()->storageLocations->pluck('name')->all())->toBe(['Main Storage Room'])
        ->and($reconciliation()->categories)->toBe([$this->category->id => 'Medical'])
        ->and($reconciliation()->locations)->toBe([$this->location->id => 'Main Storage Room']);

    $this->category->update(['name' => 'Medical Supplies']);
    $this->location->update(['name' => 'Central Store']);
    $this->supplier->update(['name' => 'Acme Medical Supply']);

    expect($stocks())->toBe([$this->category->id => 'Medical Supplies'])
        ->and($receiving()->suppliers->pluck('name')->all())->toBe(['Acme Medical Supply'])
        ->and($receiving()->storageLocations->pluck('name')->all())->toBe(['Central Store'])
        ->and($reconciliation()->categories)->toBe([$this->category->id => 'Medical Supplies'])
        ->and($reconciliation()->locations)->toBe([$this->location->id => 'Central Store']);
});

it('updates the portal item stock after a release', function () {
    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);
    Http::fake(['areas.test/*' => Http::response(['status' => 200, 'data' => []])]);
    $portalStock = fn () => (int) Livewire::test(RequestForm::class)->instance()->inventoryItems->get($this->item->id)->available_qty;

    expect($portalStock())->toBe(20);

    cacheRelease($this->item, 4);

    expect($portalStock())->toBe(16);
});

it('refreshes the transaction history user filter on a rename but not on a login save', function () {
    $users = fn () => Livewire::withoutLazyLoading()->test(TransactionHistory::class)->instance()->users->pluck('name')->all();

    expect($users())->toBe(['Cache Custodian']);
    $cached = Cache::get(InventoryCache::LOOKUP_TRANSACTION_USERS);

    $this->user->forceFill(['remember_token' => Str::random(60)])->save();
    expect(Cache::get(InventoryCache::LOOKUP_TRANSACTION_USERS))->toBe($cached);

    $this->user->update(['name' => 'Renamed Custodian']);
    expect($users())->toBe(['Renamed Custodian']);
});

/* -------------------------------------------------------------------------- */
/* One ledger version write per commit */
/* -------------------------------------------------------------------------- */

it('writes the ledger version once for a ten-row receipt', function () {
    $items = InventoryItems::factory()->count(10)->create(['is_active' => true, 'requires_expiry' => false]);
    $form = Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', $this->supplier->id)
        ->set('form.reference_number', 'DR-CACHE-010')
        ->set('form.items', $items->map(fn (InventoryItems $item) => [
            'inventory_item_id' => $item->id,
            'storage_location_id' => $this->location->id,
            'batch_number' => '',
            'lot_number' => '',
            'ordered_qty' => '',
            'quantity' => 5,
            'unit_cost' => 2,
            'manufactured_date' => null,
            'expiration_date' => null,
        ])->all())
        ->set('attachments', [UploadedFile::fake()->create('dr.pdf', 50, 'application/pdf')]);

    $writes = ledgerVersionWrites(fn () => $form->call('save')->assertHasNoErrors());

    expect($writes)->toBe(1)
        ->and(InventoryBatches::count())->toBe(11);
});

it('writes the ledger version once for a release drawing on several batches per line', function () {
    $first = InventoryItems::factory()->create(['is_active' => true, 'requires_expiry' => false]);
    $second = InventoryItems::factory()->create(['is_active' => true, 'requires_expiry' => false]);
    foreach ([$first, $second] as $item) {
        cacheStock($item, 20);
        cacheStock($item, 20);
    }
    $page = cacheOpenRelease([[$first, 30], [$second, 30]]);

    $writes = ledgerVersionWrites(fn () => $page->call('release')->assertHasNoErrors());

    expect($writes)->toBe(1)
        ->and(InventoryTransactions::where('type', InventoryTransactionType::Issuance)->count())->toBe(4);
});

it('writes nothing when the transaction throws, and the next commit still writes', function () {
    $version = InventoryCache::ledgerVersion();

    $failed = ledgerVersionWrites(function () {
        try {
            DB::transaction(function () {
                $this->item->update(['minimum_stock' => 11]);
                $this->batch->update(['remaining_qty' => 19]);

                throw new RuntimeException('roll back');
            });
        } catch (RuntimeException) {
            // expected
        }
    });

    expect($failed)->toBe(0)
        ->and(InventoryCache::ledgerVersion())->toBe($version);

    $committed = ledgerVersionWrites(fn () => DB::transaction(fn () => $this->item->update(['minimum_stock' => 12])));

    expect($committed)->toBe(1)
        ->and(InventoryCache::ledgerVersion())->not->toBe($version);
});

it('changes the version when the outer transaction commits around a rolled-back savepoint', function (bool $innerFirst) {
    $version = InventoryCache::ledgerVersion();
    $failedSavepoint = function () {
        try {
            DB::transaction(function () {
                $this->batch->update(['remaining_qty' => 1]);

                throw new RuntimeException('roll back the savepoint');
            });
        } catch (RuntimeException) {
            // expected
        }
    };

    $writes = ledgerVersionWrites(fn () => DB::transaction(function () use ($innerFirst, $failedSavepoint) {
        if ($innerFirst) {
            $failedSavepoint();
        }

        $this->item->update(['minimum_stock' => 13]);

        if (! $innerFirst) {
            $failedSavepoint();
        }
    }));

    expect($writes)->toBeGreaterThanOrEqual(1)
        ->and(InventoryCache::ledgerVersion())->not->toBe($version)
        ->and($this->batch->fresh()->remaining_qty)->toBe(20);
})->with([
    'savepoint rolls back before the outer write' => true,
    'savepoint rolls back after the outer write' => false,
]);

it('writes the version immediately when no transaction is open', function () {
    // The test wraps every case in a transaction, so check the no-transaction
    // branch on a connection that has none open.
    config(['database.connections.cache_no_transaction' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    $default = DB::getDefaultConnection();
    $version = InventoryCache::ledgerVersion();

    DB::setDefaultConnection('cache_no_transaction');

    try {
        $level = DB::transactionLevel();
        $writes = ledgerVersionWrites(fn () => InventoryCache::bumpLedger());
    } finally {
        DB::setDefaultConnection($default);
    }

    expect($level)->toBe(0)
        ->and($writes)->toBe(1)
        ->and(InventoryCache::ledgerVersion())->not->toBe($version);
});

it('does not skip a later bump after a queue job ends with one still pending', function (Closure $jobEnded) {
    // An earlier job bumps inside a transaction that is lost without a rollback
    // event (say the worker died), so its pending bump is never written.
    $connection = DB::connection();
    $dispatcher = $connection->getEventDispatcher();
    $connection->beginTransaction();
    InventoryCache::bumpLedger();
    $connection->unsetEventDispatcher();
    $connection->rollBack();
    $connection->setEventDispatcher($dispatcher);

    $pending = new ReflectionProperty(InventoryCache::class, 'pendingBump');
    expect($pending->getValue())->toBeTrue();

    event($jobEnded());

    $version = InventoryCache::ledgerVersion();
    $writes = ledgerVersionWrites(fn () => DB::transaction(fn () => $this->item->update(['minimum_stock' => 14])));

    expect($writes)->toBe(1)
        ->and(InventoryCache::ledgerVersion())->not->toBe($version);
})->with([
    // Passed through as closures (the parameter is typed Closure), built when called.
    'job processed' => fn () => new JobProcessed('database', Mockery::mock(Job::class)),
    'job failed' => fn () => new JobFailed('database', Mockery::mock(Job::class), new RuntimeException('boom')),
]);

it('keeps the receiving item list out of the cache but still caches the other lookups', function () {
    $written = [];
    Event::listen(KeyWritten::class, function (KeyWritten $event) use (&$written): void {
        $written[] = $event->key;
    });

    $form = Livewire::test(ReceivingCreateForm::class)->assertHasNoErrors();
    $receivingKeys = $written;
    Livewire::withoutLazyLoading()->test(Stocks::class)->assertHasNoErrors();

    expect($form->instance()->inventoryItems->pluck('name')->all())->toContain('Gauze Roll')
        ->and(collect($receivingKeys)->sort()->values()->all())
        ->toBe([InventoryCache::LOOKUP_STORAGE_LOCATIONS, InventoryCache::LOOKUP_SUPPLIERS])
        ->and(Cache::has(InventoryCache::LOOKUP_CATEGORIES))->toBeTrue();
});
