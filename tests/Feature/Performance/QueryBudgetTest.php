<?php

use App\Enums\ApprovalDecision;
use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Categories;
use App\Livewire\Inventory\Items;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Monitoring\Reports;
use App\Livewire\Monitoring\StockDetail;
use App\Livewire\Monitoring\StockMonitoring;
use App\Livewire\Monitoring\StockReconciliation;
use App\Livewire\Monitoring\TransactionHistory;
use App\Livewire\Portal\RequestForm;
use App\Livewire\Requisitions\Issuances;
use App\Models\Approvals;
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
use App\Services\Inventory\StockStatusMonitor;
use Database\Seeders\InventoryAdjustmentsSeeder;
use Database\Seeders\InventoryBatchesSeeder;
use Database\Seeders\InventoryCategoriesSeeder;
use Database\Seeders\InventoryItemsSeeder;
use Database\Seeders\StockReceiptsSeeder;
use Database\Seeders\StorageLocationsSeeder;
use Database\Seeders\SuppliersSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * Performance budgets for the main pages and write paths. On the seeded dataset
 * each target must stay within its PERF_QUERY_BUDGETS query count; every number
 * is also printed to STDERR as a "[query-budget] ..." line. Timings are only
 * reported, never asserted.
 *
 * The default tests use the seeded dataset as is (query counts only). The `perf`
 * group re-runs the targets on a 5x dataset and reports median SQL time; it is
 * slow, so it only runs when PERF_BASELINE=1 is set:
 *
 *     PERF_BASELINE=1 php artisan test --group=perf tests/Feature/Performance/QueryBudgetTest.php
 */

const PERF_DASHBOARD_ISLANDS = ['needs-action', 'queue-health', 'lists', 'movement', 'activity'];

const PERF_RUNS = 5;

/**
 * Upper bounds on the queries each target runs on the seeded dataset: the count
 * measured after the performance pass plus one. Raise a bound only on purpose.
 */
const PERF_QUERY_BUDGETS = [
    'Dashboard (shell)' => 1,
    'Dashboard island: needs-action' => 6,
    'Dashboard island: queue-health' => 4,
    'Dashboard island: lists' => 3,
    'Dashboard island: movement' => 3,
    'Dashboard island: activity' => 7, // measures 5 or 6 depending on the seeded activity
    'Layout shell (GET inventory.stocks)' => 1,
    'Inventory: Stocks' => 9,
    'Inventory: Items' => 4,
    'Inventory: Categories' => 3,
    'Monitoring: StockMonitoring list' => 6,
    'Monitoring: StockDetail (row detail load)' => 7,
    'Monitoring: StockReconciliation' => 10,
    'Monitoring: Reports' => 9,
    'Monitoring: TransactionHistory' => 12,
    'Requisitions: Issuances list' => 4,
    // The preview loads batch queues with BatchAllocator::queueFor(): one query per
    // allocation method in the requisition, so a FEFO (requires_expiry) batch query
    // comes and goes depending on whether the seeded requisition mixes perishable and
    // non-perishable items. The seeders shuffle with Laravel's secure randomizer,
    // which mt_srand() does not seed, so the mix varies between runs (12-13 / 1-2).
    'Requisitions: Issuances open (render + preview)' => 14,
    'Requisitions: Issuances preview() alone' => 3,
    'Receiving: create form' => 4,
    'Portal: RequestForm' => 2,
];

afterEach(function () {
    if (isset($this->perfCachePath)) {
        File::deleteDirectory($this->perfCachePath);
    }
});

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 6, 15)->setTime(10, 0));
    // The seeders also shuffle and pick at random, so seed PHP's engine too.
    fake()->seed(20260615);
    mt_srand(20260615);

    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);
    Http::preventStrayRequests();
    Http::fake([
        'areas.test/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 6, 'name' => 'Finance Office', 'alias' => 'Finance', 'abbreviation' => 'FO', 'type' => 'office', 'parent_id' => null],
                ['id' => 48, 'name' => 'Accounting Section', 'alias' => 'Accounting Section', 'abbreviation' => 'AS', 'type' => 'section', 'parent_id' => 6],
            ],
        ]),
    ]);
    Notification::fake();
    Mail::fake();
    Storage::fake('public');

    // Records each query's time and transaction depth while a measurement runs.
    perfRecorder()->queries = null;
    DB::listen(function (QueryExecuted $query) {
        if (is_array(perfRecorder()->queries)) {
            perfRecorder()->queries[] = ['ms' => $query->time, 'level' => $query->connection->transactionLevel()];
        }
    });
});

/**
 * The queries recorded by the running measurement, or null when none is running.
 */
function perfRecorder(): stdClass
{
    static $recorder;

    return $recorder ??= (object) ['queries' => null];
}

/* -------------------------------------------------------------------------- */
/* Dataset */
/* -------------------------------------------------------------------------- */

/**
 * Seeds the project's inventory seeders (38 items, receipts, batches with expired
 * and expiring-soon stock, adjustments and their ledger rows), then requisitions
 * in every status, with real issuances released through the Issuances page.
 *
 * A $scale above 1 adds ($scale - 1) x 38 more items with 12 batches each, issuance
 * history for them, and $scale times the requisitions and adjustments.
 *
 * @return Requisitions an approved, unreleased requisition with 3 lines
 */
function perfSeed(int $scale = 1): Requisitions
{
    $user = User::factory()->create(['name' => 'Perf Custodian']);
    test()->actingAs($user);

    $monitor = app(StockStatusMonitor::class);
    $monitor->muted(function () use ($scale, $user) {
        test()->seed([
            StorageLocationsSeeder::class,
            SuppliersSeeder::class,
            InventoryCategoriesSeeder::class,
            InventoryItemsSeeder::class,
            StockReceiptsSeeder::class,
            InventoryBatchesSeeder::class,
        ]);

        if ($scale > 1) {
            perfScaleUp($scale, $user);
        }

        foreach (range(1, $scale) as $pass) {
            test()->seed(InventoryAdjustmentsSeeder::class);
        }
    });
    $monitor->baseline();

    $stocked = InventoryItems::query()
        ->withAvailableQty()
        ->get()
        ->filter(fn (InventoryItems $item) => (int) $item->available_qty >= 20)
        ->values();

    $statuses = [
        RequisitionStatus::Pending, RequisitionStatus::Pending, RequisitionStatus::Pending,
        RequisitionStatus::Rejected, RequisitionStatus::Cancelled,
        RequisitionStatus::Approved, RequisitionStatus::Approved, RequisitionStatus::Approved,
        RequisitionStatus::Approved,
    ];

    $untouched = null;

    foreach (range(1, $scale) as $pass) {
        $requisitions = collect($statuses)->map(function (RequisitionStatus $status, int $index) use ($user, $stocked, $pass) {
            $number = ($pass - 1) * 9 + $index;

            $requisition = Requisitions::create([
                'request_number' => sprintf('REQ-PERF-%04d', $number + 1),
                'user_id' => $user->id,
                'status' => $status,
                'purpose' => 'Monthly supplies',
                'submitted_at' => now()->subDays(10 - $index)->subHours($pass),
            ]);

            foreach ($stocked->slice(($number * 3) % max(1, $stocked->count() - 3), 3) as $item) {
                $approvedQty = $status === RequisitionStatus::Approved ? 4 : 0;

                $requisition->items()->create([
                    'inventory_item_id' => $item->id,
                    'requested_qty' => 4,
                    'available_qty' => (int) $item->available_qty,
                    'approved_qty' => $approvedQty,
                    'released_qty' => 0,
                    'outstanding_qty' => $approvedQty,
                ]);
            }

            if (in_array($status, [RequisitionStatus::Approved, RequisitionStatus::Rejected], true)) {
                Approvals::create([
                    'requisition_id' => $requisition->id,
                    'user_id' => $user->id,
                    'decision' => $status === RequisitionStatus::Rejected ? ApprovalDecision::Rejected : ApprovalDecision::Approved,
                    'remarks' => 'Reviewed',
                    'decided_at' => now()->subDays(5),
                ]);
            }

            return $requisition->load('items');
        });

        [$fullOne, $fullTwo, $partial, $untouched] = $requisitions
            ->filter(fn (Requisitions $requisition) => $requisition->status === RequisitionStatus::Approved)
            ->values()
            ->all();

        foreach ([$fullOne, $fullTwo] as $requisition) {
            Livewire::withoutLazyLoading()->test(Issuances::class)
                ->call('open', $requisition->id)
                ->call('release')
                ->assertHasNoErrors();
        }

        Livewire::withoutLazyLoading()->test(Issuances::class)
            ->call('open', $partial->id)
            ->set("form.release.{$partial->items->first()->id}", 1)
            ->call('release')
            ->assertHasNoErrors();
    }

    return $untouched;
}

/**
 * Adds ($scale - 1) x 38 items, each with 12 batches from extra receipts. The older
 * batches are drawn down by issuances (real issuance documents, pooled into
 * 20 x $scale requisitions), and every movement gets a ledger row with a running
 * balance_after.
 */
function perfScaleUp(int $scale, User $user): void
{
    $extra = $scale - 1;
    $categories = InventoryCategories::pluck('id');
    $locations = StorageLocations::pluck('id');
    $suppliers = Suppliers::query()->where('is_active', true)->pluck('id');

    $receipts = StockReceipts::factory()
        ->complete()
        ->count(23 * $extra)
        ->state(fn () => ['supplier_id' => $suppliers->random(), 'received_by' => $user->id])
        ->create();

    $items = InventoryItems::factory()
        ->count(38 * $extra)
        ->sequence(fn (Sequence $sequence) => ['requires_expiry' => $sequence->index % 4 === 0])
        ->create(['is_active' => true]);

    $pool = collect(range(1, 20 * $scale))->map(function (int $n) use ($user) {
        $requisition = Requisitions::create([
            'request_number' => sprintf('REQ-SCALE-%05d', $n),
            'user_id' => $user->id,
            'status' => RequisitionStatus::Issued,
            'purpose' => 'Scaled issuance history',
            'submitted_at' => now()->subDays(fake()->numberBetween(2, 180)),
        ]);

        return StockIssuances::create([
            'requisition_id' => $requisition->id,
            'user_id' => $user->id,
            'issuance_number' => sprintf('ISS-SCALE-%05d', $n),
            'released_by' => $user->name,
            'received_by' => 'Scaled Receiver',
            'released_at' => now()->subDay(),
        ]);
    });

    $batchNumber = 0;
    $draws = 0;

    foreach ($items as $item) {
        $item->inventoryCategory()->attach($categories->random());

        $events = [];

        foreach ($receipts->random(12)->sortBy('received_date')->values() as $index => $receipt) {
            $receivedAt = Carbon::parse($receipt->received_date);
            $quantity = fake()->numberBetween(20, 120);
            $consumed = $index < 8 ? fake()->numberBetween(intdiv($quantity, 2), $quantity) : 0;
            $batchNumber++;

            $batch = InventoryBatches::factory()->create([
                'inventory_item_id' => $item->id,
                'stock_receipt_id' => $receipt->id,
                'storage_location_id' => $locations->random(),
                'batch_number' => sprintf('BAT-PERF-%06d', $batchNumber),
                'lot_number' => sprintf('LOT-PERF-%06d', $batchNumber),
                'received_qty' => $quantity,
                'remaining_qty' => $quantity - $consumed,
                'received_at' => $receivedAt,
                'manufactured_date' => $item->requires_expiry ? $receivedAt->copy()->subDays(60)->toDateString() : null,
                'expiration_date' => $item->requires_expiry ? perfExpiry($index) : null,
                'unit_cost' => fake()->randomFloat(2, 8, 1800),
                'created_at' => $receivedAt,
                'updated_at' => $receivedAt,
            ]);

            $events[] = ['at' => $receivedAt, 'batch' => $batch, 'quantity' => $quantity, 'receipt' => $receipt];

            if ($consumed > 0) {
                $issuedAt = $receivedAt->copy()->addDays(fake()->numberBetween(1, 20))->min(now()->subHour());
                $events[] = ['at' => $issuedAt, 'batch' => $batch, 'quantity' => -$consumed, 'receipt' => null];
            }
        }

        usort($events, fn (array $a, array $b) => $a['at'] <=> $b['at']);
        $balance = 0;

        foreach ($events as $event) {
            $balance += $event['quantity'];
            $reference = $event['receipt'];

            if ($reference === null) {
                $reference = $pool[$draws++ % $pool->count()];
                perfIssuanceLine($reference, $item, $event['batch'], -$event['quantity']);
            }

            $transaction = new InventoryTransactions([
                'inventory_item_id' => $item->id,
                'batch_id' => $event['batch']->id,
                'user_id' => $user->id,
                'type' => $event['receipt'] ? InventoryTransactionType::Receipt : InventoryTransactionType::Issuance,
                'quantity' => $event['quantity'],
                'balance_after' => $balance,
                'reference_type' => $reference::class,
                'reference_id' => $reference->id,
            ]);
            $transaction->created_at = $event['at'];
            $transaction->updated_at = $event['at'];
            $transaction->save();
        }
    }
}

/**
 * Perishable batches by age: the oldest has expired, the next expires within 30 days.
 */
function perfExpiry(int $index): string
{
    return match ($index) {
        0 => today()->subDays(fake()->numberBetween(1, 60))->toDateString(),
        1 => today()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        default => today()->addDays(fake()->numberBetween(60, 730))->toDateString(),
    };
}

/**
 * Records one batch draw on a pooled issuance: a requisition line, the issued line
 * and the batch it came from.
 */
function perfIssuanceLine(StockIssuances $issuance, InventoryItems $item, InventoryBatches $batch, int $quantity): void
{
    $line = Requisitions::query()->findOrFail($issuance->requisition_id)->items()->create([
        'inventory_item_id' => $item->id,
        'requested_qty' => $quantity,
        'available_qty' => $quantity,
        'approved_qty' => $quantity,
        'released_qty' => $quantity,
        'outstanding_qty' => 0,
    ]);

    $issuance->stockIssuanceItems()
        ->create(['requisition_item_id' => $line->id, 'released_qty' => $quantity])
        ->batches()
        ->create(['batch_id' => $batch->id, 'quantity' => $quantity]);
}

/**
 * An item stocked from its own receipt, one batch per quantity (oldest first),
 * with the matching receipt ledger rows.
 *
 * @param  list<int>  $quantities
 */
function perfStockedItem(array $quantities, bool $requiresExpiry = false): InventoryItems
{
    $item = InventoryItems::factory()->create(['is_active' => true, 'requires_expiry' => $requiresExpiry]);
    $receipt = StockReceipts::factory()->complete()->receivedOn(now()->subDays(30))->create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => auth()->id(),
    ]);
    $location = StorageLocations::query()->value('id');
    $balance = 0;

    foreach ($quantities as $index => $quantity) {
        $batch = InventoryBatches::factory()->create([
            'inventory_item_id' => $item->id,
            'stock_receipt_id' => $receipt->id,
            'storage_location_id' => $location,
            'received_qty' => $quantity,
            'remaining_qty' => $quantity,
            'received_at' => now()->subDays(30 - $index),
            'expiration_date' => $requiresExpiry ? today()->addDays(60 + $index * 30)->toDateString() : null,
        ]);
        $balance += $quantity;

        InventoryTransactions::create([
            'inventory_item_id' => $item->id,
            'batch_id' => $batch->id,
            'user_id' => auth()->id(),
            'type' => InventoryTransactionType::Receipt,
            'quantity' => $quantity,
            'balance_after' => $balance,
            'reference_type' => StockReceipts::class,
            'reference_id' => $receipt->id,
        ]);
    }

    return $item;
}

function perfPrintVolume(): void
{
    fwrite(STDERR, sprintf(
        "[query-budget] dataset: %d items, %d batches, %d transactions, %d requisitions, %d issuances\n",
        InventoryItems::count(),
        InventoryBatches::count(),
        InventoryTransactions::count(),
        Requisitions::count(),
        StockIssuances::count(),
    ));
}

/* -------------------------------------------------------------------------- */
/* Measuring */
/* -------------------------------------------------------------------------- */

/**
 * Counts the queries the callback runs and prints them for the baseline table.
 */
function perfQueries(string $label, callable $callback): int
{
    perfRecorder()->queries = [];
    $callback();
    $count = count(perfRecorder()->queries);
    perfRecorder()->queries = null;

    $budget = PERF_QUERY_BUDGETS[$label] ?? null;
    fwrite(STDERR, sprintf("[query-budget] %-48s %4d queries (budget %s)\n", $label, $count, $budget ?? '-'));

    expect($budget)->not->toBeNull("No query budget is set for [{$label}]")
        ->and($count)->toBeLessThanOrEqual($budget, "[{$label}] ran {$count} queries, over its budget of {$budget}");

    return $count;
}

/**
 * Points the cache at a file store in a temporary directory, as production
 * uses, so cached reads pay the real (de)serialization cost.
 */
function perfUseFileCache(): void
{
    test()->perfCachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inventory-perf-cache-'.Str::random(12);
    config([
        'cache.stores.file.path' => test()->perfCachePath,
        'cache.stores.file.lock_path' => test()->perfCachePath.DIRECTORY_SEPARATOR.'locks',
    ]);
    Cache::forgetDriver('file');
    Cache::setDefaultDriver('file');
}

/**
 * Measures a read twice: cold (cache flushed before every run, as the first
 * view after any stock change) and warm (served from the cache).
 */
function perfMeasureColdWarm(string $label, callable $setup, callable $action): void
{
    perfMeasure("{$label} [cold]", function (int $run) use ($setup) {
        Cache::flush();

        return $setup($run);
    }, $action);

    perfMeasure("{$label} [warm]", $setup, $action);
}

/**
 * Runs $action once to warm up, then PERF_RUNS times, each on fresh state from
 * $setup (which is not measured), and prints the medians. "Core" covers the
 * queries inside the action's own DB transaction, i.e. the write itself without
 * validation or the re-render.
 *
 * @return array{queries: int, sql_ms: float, wall_ms: float, core_queries: int, core_ms: float}
 */
function perfMeasure(string $label, callable $setup, callable $action): array
{
    $samples = [];

    foreach (range(0, PERF_RUNS) as $run) {
        $state = $setup($run);
        $outerLevel = DB::transactionLevel();

        perfRecorder()->queries = [];
        $started = hrtime(true);
        $action($state);
        $wallMs = (hrtime(true) - $started) / 1e6;
        $log = perfRecorder()->queries;
        perfRecorder()->queries = null;

        if ($run === 0) {
            continue;
        }

        $core = array_filter($log, fn (array $query) => $query['level'] > $outerLevel);

        $samples[] = [
            'queries' => count($log),
            'sql_ms' => array_sum(array_column($log, 'ms')),
            'wall_ms' => $wallMs,
            'core_queries' => count($core),
            'core_ms' => array_sum(array_column($core, 'ms')),
        ];
    }

    $median = fn (string $key) => collect($samples)->pluck($key)->median();
    $counts = collect($samples)->pluck('queries');
    $result = [
        'queries' => (int) $median('queries'),
        'sql_ms' => round($median('sql_ms'), 2),
        'wall_ms' => round($median('wall_ms'), 1),
        'core_queries' => (int) $median('core_queries'),
        'core_ms' => round($median('core_ms'), 2),
    ];

    fwrite(STDERR, sprintf(
        "[query-budget] %-48s %4d queries (%s)  sql %7.2f ms  wall %7.1f ms  core %3d q / %6.2f ms\n",
        $label,
        $result['queries'],
        $counts->min() === $counts->max() ? 'stable' : "{$counts->min()}-{$counts->max()}",
        $result['sql_ms'],
        $result['wall_ms'],
        $result['core_queries'],
        $result['core_ms'],
    ));

    return $result;
}

/* -------------------------------------------------------------------------- */
/* Query counts on the seeded dataset */
/* -------------------------------------------------------------------------- */

it('renders the dashboard and loads each of its islands', function () {
    perfSeed();

    $page = null;
    perfQueries('Dashboard (shell)', function () use (&$page) {
        $page = Livewire::test(Dashboard::class);
    });

    foreach (PERF_DASHBOARD_ISLANDS as $island) {
        $html = '';
        perfQueries("Dashboard island: {$island}", function () use ($page, $island, &$html) {
            $html = perfLoadIsland($page, $island);
        });

        expect($html)->not->toBeEmpty("island {$island} should render content");
    }
});

it('renders the app layout around a deferred page', function () {
    perfSeed();

    perfQueries('Layout shell (GET inventory.stocks)', fn () => $this->get(route('inventory.stocks'))->assertOk());
});

it('renders the stocks page', function () {
    perfSeed();

    perfQueries('Inventory: Stocks', fn () => Livewire::withoutLazyLoading()->test(Stocks::class)->assertHasNoErrors());
});

it('renders the items page', function () {
    perfSeed();

    perfQueries('Inventory: Items', fn () => Livewire::withoutLazyLoading()->test(Items::class)->assertHasNoErrors());
});

it('renders the categories page', function () {
    perfSeed();

    perfQueries('Inventory: Categories', fn () => Livewire::withoutLazyLoading()->test(Categories::class)->assertHasNoErrors());
});

it('renders the stock monitoring list and an item detail', function () {
    perfSeed();
    $item = perfItemWithMostBatches();

    // Replays the browser's deferred load, so each row's lazy StockDetail stays a placeholder.
    $placeholder = perfDeferredPlaceholder(StockMonitoring::class);
    perfQueries('Monitoring: StockMonitoring list', fn () => perfDeferredLoad($placeholder));

    perfQueries('Monitoring: StockDetail (row detail load)', fn () => Livewire::withoutLazyLoading()
        ->test(StockDetail::class, ['itemId' => $item])
        ->assertDontSee('This item is no longer available.'));
});

it('renders the stock reconciliation page', function () {
    perfSeed();

    perfQueries('Monitoring: StockReconciliation', fn () => Livewire::withoutLazyLoading()->test(StockReconciliation::class)->assertHasNoErrors());
});

it('renders the reports page', function () {
    perfSeed();

    perfQueries('Monitoring: Reports', fn () => Livewire::withoutLazyLoading()->test(Reports::class)->assertHasNoErrors());
});

it('renders the transaction history page', function () {
    perfSeed();

    perfQueries('Monitoring: TransactionHistory', fn () => Livewire::withoutLazyLoading()->test(TransactionHistory::class)->assertHasNoErrors());
});

it('opens a multi-line requisition for release and previews its batches', function () {
    $requisition = perfSeed();
    expect($requisition->items)->toHaveCount(3);

    $page = null;
    perfQueries('Requisitions: Issuances list', function () use (&$page) {
        $page = Livewire::withoutLazyLoading()->test(Issuances::class);
    });

    perfQueries('Requisitions: Issuances open (render + preview)', fn () => $page->call('open', $requisition->id)->assertHasNoErrors());

    $component = $page->instance();
    unset($component->preview);
    $preview = [];
    perfQueries('Requisitions: Issuances preview() alone', function () use ($component, &$preview) {
        $preview = $component->preview;
    });

    expect($preview)->toHaveCount(3);
});

it('renders the receiving create form', function () {
    perfSeed();

    perfQueries('Receiving: create form', fn () => Livewire::test(ReceivingCreateForm::class)->assertHasNoErrors());
});

it('renders the portal request form', function () {
    perfSeed();
    auth()->logout();

    perfQueries('Portal: RequestForm', fn () => Livewire::test(RequestForm::class)->assertHasNoErrors());

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://areas.test/'));
});

/* -------------------------------------------------------------------------- */
/* Timing on a 5x dataset (opt-in: PERF_BASELINE=1, --group=perf) */
/* -------------------------------------------------------------------------- */

it('times the write paths on a 5x dataset', function () {
    perfUseFileCache();
    perfSeed(5);
    perfPrintVolume();

    $released = [];
    perfMeasure(
        'Write: issuance release (3 lines x 2 batches)',
        function () use (&$released) {
            $items = collect([perfStockedItem([6, 6, 6]), perfStockedItem([6, 6, 6]), perfStockedItem([6, 6, 6], requiresExpiry: true)]);

            $requisition = Requisitions::create([
                'request_number' => sprintf('REQ-WRITE-%04d', count($released) + 1),
                'user_id' => auth()->id(),
                'status' => RequisitionStatus::Approved,
                'purpose' => 'Write path timing',
                'submitted_at' => now(),
            ]);

            foreach ($items as $item) {
                $requisition->items()->create([
                    'inventory_item_id' => $item->id,
                    'requested_qty' => 10,
                    'available_qty' => 18,
                    'approved_qty' => 10,
                    'released_qty' => 0,
                    'outstanding_qty' => 10,
                ]);
            }

            $released[] = $requisition->id;

            return Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
        },
        fn ($page) => $page->call('release')->assertHasNoErrors(),
    );

    foreach ($released as $requisitionId) {
        expect(Requisitions::find($requisitionId)->status)->toBe(RequisitionStatus::Issued)
            ->and(StockIssuanceBatches::query()
                ->whereHas('stockIssuanceItem.stockIssuance', fn ($query) => $query->where('requisition_id', $requisitionId))
                ->count())->toBe(6);
    }

    $receiptItems = InventoryItems::query()->where('is_active', true)->orderBy('id')->limit(10)->get();
    $location = StorageLocations::query()->value('id');
    $supplier = Suppliers::query()->where('is_active', true)->value('id');
    $receiptsBefore = StockReceipts::count();

    perfMeasure(
        'Write: stock receipt (10 rows)',
        fn (int $run) => Livewire::test(ReceivingCreateForm::class)
            ->set('form.supplier_id', $supplier)
            ->set('form.reference_number', sprintf('DR-WRITE-%04d', $run))
            ->set('form.items', $receiptItems->map(fn (InventoryItems $item) => [
                'inventory_item_id' => $item->id,
                'storage_location_id' => $location,
                'batch_number' => '',
                'lot_number' => '',
                'ordered_qty' => '',
                'quantity' => 25,
                'unit_cost' => 12.5,
                'manufactured_date' => null,
                'expiration_date' => $item->requires_expiry ? today()->addYear()->toDateString() : null,
            ])->all())
            ->set('attachments', [UploadedFile::fake()->create('dr.pdf', 50, 'application/pdf')]),
        fn ($page) => $page->call('save')->assertHasNoErrors(),
    );

    expect(StockReceipts::count())->toBe($receiptsBefore + PERF_RUNS + 1);
})->group('perf')->skip(fn () => ! env('PERF_BASELINE'), 'Set PERF_BASELINE=1 to run the 5x timing baseline.');

it('times the read paths on a 5x dataset', function () {
    perfUseFileCache();
    $requisition = perfSeed(5);
    perfPrintVolume();
    $item = perfItemWithMostBatches();
    $fresh = fn () => null;

    perfMeasureColdWarm('Dashboard (shell)', $fresh, fn () => Livewire::test(Dashboard::class));

    foreach (PERF_DASHBOARD_ISLANDS as $island) {
        perfMeasureColdWarm(
            "Dashboard island: {$island}",
            fn () => Livewire::test(Dashboard::class),
            fn ($page) => expect(perfLoadIsland($page, $island))->not->toBeEmpty(),
        );
    }

    perfMeasureColdWarm('Layout shell (GET inventory.stocks)', $fresh, fn () => $this->get(route('inventory.stocks'))->assertOk());

    $pages = [
        'Inventory: Stocks' => Stocks::class,
        'Inventory: Items' => Items::class,
        'Inventory: Categories' => Categories::class,
        'Monitoring: StockReconciliation' => StockReconciliation::class,
        'Monitoring: Reports' => Reports::class,
        'Monitoring: TransactionHistory' => TransactionHistory::class,
        'Requisitions: Issuances list' => Issuances::class,
    ];

    foreach ($pages as $label => $component) {
        perfMeasureColdWarm($label, $fresh, fn () => Livewire::withoutLazyLoading()->test($component)->assertHasNoErrors());
    }

    // Its rows hold lazy StockDetail children, which withoutLazyLoading() would
    // mount eagerly; replay the browser's deferred load so they stay placeholders.
    perfMeasureColdWarm(
        'Monitoring: StockMonitoring list',
        fn () => perfDeferredPlaceholder(StockMonitoring::class),
        fn (array $placeholder) => perfDeferredLoad($placeholder),
    );

    perfMeasureColdWarm(
        'Monitoring: StockDetail (row detail load)',
        $fresh,
        fn () => Livewire::withoutLazyLoading()
            ->test(StockDetail::class, ['itemId' => $item])
            ->assertDontSee('This item is no longer available.'),
    );

    perfMeasureColdWarm(
        'Requisitions: Issuances open (render + preview)',
        fn () => Livewire::withoutLazyLoading()->test(Issuances::class),
        fn ($page) => $page->call('open', $requisition->id)->assertHasNoErrors(),
    );

    perfMeasureColdWarm(
        'Requisitions: Issuances preview() alone',
        function () use ($requisition) {
            $component = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->instance();
            unset($component->preview);

            return $component;
        },
        fn ($component) => expect($component->preview)->toHaveCount(3),
    );

    perfMeasureColdWarm('Receiving: create form', $fresh, fn () => Livewire::test(ReceivingCreateForm::class)->assertHasNoErrors());

    perfMeasureColdWarm(
        'Portal: RequestForm',
        fn () => auth()->logout(),
        fn () => Livewire::test(RequestForm::class)->assertHasNoErrors(),
    );
})->group('perf')->skip(fn () => ! env('PERF_BASELINE'), 'Set PERF_BASELINE=1 to run the 5x timing baseline.');

/* -------------------------------------------------------------------------- */
/* Helpers */
/* -------------------------------------------------------------------------- */

/**
 * Renders a #[Defer] page's placeholder, as the first page load does.
 *
 * @param  class-string  $component
 * @return array{page: Testable, payload: string}
 */
function perfDeferredPlaceholder(string $component): array
{
    $page = Livewire::test($component);

    // The quotes around the encoded mount params may be HTML-escaped in the attribute.
    preg_match('/__lazyLoad\((?:\'|&#0?39;)([A-Za-z0-9+\/=]+)/', $page->html(), $match);

    return ['page' => $page, 'payload' => $match[1]];
}

/**
 * The browser's follow-up `__lazyLoad` call that renders the page's real content.
 *
 * @param  array{page: Testable, payload: string}  $placeholder
 */
function perfDeferredLoad(array $placeholder): Testable
{
    return $placeholder['page']->call('__lazyLoad', $placeholder['payload'])->assertHasNoErrors();
}

/**
 * Loads a lazy or deferred dashboard island the way the browser does and returns its HTML.
 */
function perfLoadIsland($page, string $island): string
{
    $page->update(calls: [[
        'method' => '__lazyLoadIsland',
        'params' => [],
        'path' => '',
        'metadata' => ['island' => ['name' => $island, 'mode' => 'morph']],
    ]]);

    return implode('', $page->effects['islandFragments'] ?? []);
}

function perfItemWithMostBatches(): int
{
    return InventoryBatches::query()
        ->selectRaw('inventory_item_id, count(*) as batches')
        ->groupBy('inventory_item_id')
        ->orderByDesc('batches')
        ->value('inventory_item_id');
}
