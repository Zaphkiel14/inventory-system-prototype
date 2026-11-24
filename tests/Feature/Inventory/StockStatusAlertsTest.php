<?php

use App\Enums\AdjustmentType;
use App\Enums\RequisitionStatus;
use App\Enums\StockStatus;
use App\Livewire\Inventory\AdjustmentCreateForm;
use App\Livewire\Inventory\ItemEditForm;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Requisitions\Issuances;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Notifications\StockStatusAlert;
use App\Services\Inventory\StockStatusMonitor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
});

test('seeding the database sends no stock alerts', function () {
    $this->seed();

    Notification::assertSentTimes(StockStatusAlert::class, 0);

    // Seeding ends with a baseline, so a later check has nothing to report.
    expect(app(StockStatusMonitor::class)->checkAll())->toBeEmpty();
    Notification::assertSentTimes(StockStatusAlert::class, 0);
});

/**
 * An active item with minimum stock 5 and reorder level 20:
 * Healthy > 20, Reorder 6-20, Critical 1-5, Out 0.
 */
function alertItem(array $attributes = []): InventoryItems
{
    return InventoryItems::factory()->create([
        'minimum_stock' => 5,
        'reorder_level' => 20,
        'requires_expiry' => false,
        'is_active' => true,
        ...$attributes,
    ]);
}

/**
 * Stock placed directly (no ledger row, so no alert); call alertBaseline()
 * afterwards to record the starting status.
 */
function alertBatch(InventoryItems $item, int $qty, $expires = null): InventoryBatches
{
    static $n = 0;
    $n++;

    return InventoryBatches::create([
        'inventory_item_id' => $item->id,
        'stock_receipt_id' => test()->receipt->id,
        'storage_location_id' => test()->location->id,
        'batch_number' => "ALERT-B-{$n}",
        'received_qty' => $qty,
        'remaining_qty' => $qty,
        'expiration_date' => $expires,
        'received_at' => now()->subDays(10),
        'unit_cost' => 10,
    ]);
}

function alertBaseline(): void
{
    app(StockStatusMonitor::class)->baseline();
}

/**
 * Approve a requisition for the given lines and release it in full through the
 * Issuances page.
 *
 * @param  array<int, array{0: InventoryItems, 1: int}>  $lines
 */
function alertIssue(array $lines): void
{
    static $n = 0;
    $n++;

    $requisition = Requisitions::create([
        'request_number' => sprintf('REQ-ALERT-%04d', $n),
        'user_id' => test()->custodian->id,
        'status' => RequisitionStatus::Approved,
        'purpose' => 'Alert test',
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

    Livewire::test(Issuances::class)
        ->call('open', $requisition->id)
        ->call('release')
        ->assertHasNoErrors();
}

function alertReceive(InventoryItems $item, int $qty): void
{
    static $n = 0;
    $n++;

    Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', test()->supplier->id)
        ->set('form.reference_number', "DR-ALERT-{$n}")
        ->set('form.items.0.inventory_item_id', $item->id)
        ->set('form.items.0.storage_location_id', test()->location->id)
        ->set('form.items.0.quantity', $qty)
        ->set('form.items.0.unit_cost', 10)
        ->call('save')
        ->assertHasNoErrors();
}

/**
 * @return Collection<int, StockStatusAlert>
 */
function alertsSentTo(User $user)
{
    return Notification::sent($user, StockStatusAlert::class)->values();
}

// Users, a supplier, a storage location and a receipt. Kept out of the
// file-wide beforeEach so the seeding test starts from an empty database.
describe('stock movements', function () {
    beforeEach(function () {
        $this->custodian = User::factory()->create(['name' => 'Sam Custodian', 'notify_stock_alerts' => true]);
        $this->colleague = User::factory()->create(['name' => 'Lee Colleague', 'notify_stock_alerts' => true]);
        $this->optedOut = User::factory()->create(['name' => 'Pat OptedOut', 'notify_stock_alerts' => false]);
        $this->actingAs($this->custodian);

        InventoryCategories::factory()->create();

        $this->supplier = Suppliers::factory()->create(['is_active' => true]);
        $this->location = StorageLocations::factory()->create();

        $this->receipt = StockReceipts::create([
            'supplier_id' => $this->supplier->id,
            'received_by' => $this->custodian->id,
            'reference_number' => 'DR-ALERT-SETUP',
            'received_date' => today(),
            'status' => 'complete_delivery',
        ]);
    });

    test('an issuance that drops an item to reorder alerts every opted-in user', function () {
        $item = alertItem(['name' => 'A4 Copy Paper']);
        alertBatch($item, 30);
        alertBaseline();

        expect($item->fresh()->stock_status)->toBe(StockStatus::Healthy);

        alertIssue([[$item, 15]]);

        Notification::assertSentTimes(StockStatusAlert::class, 2);
        Notification::assertNotSentTo($this->optedOut, StockStatusAlert::class);

        foreach ([$this->custodian, $this->colleague] as $user) {
            $alerts = alertsSentTo($user);
            expect($alerts)->toHaveCount(1)
                ->and($alerts[0]->items)->toHaveCount(1)
                ->and($alerts[0]->items[0]['name'])->toBe('A4 Copy Paper')
                ->and($alerts[0]->items[0]['previous_status'])->toBe('healthy')
                ->and($alerts[0]->items[0]['status'])->toBe('reorder')
                ->and($alerts[0]->items[0]['available_qty'])->toBe(15);
        }

        expect($item->fresh()->stock_status)->toBe(StockStatus::Reorder);
    });

    test('each drop from reorder to critical to out alerts, and a receipt that improves the status does not', function () {
        $item = alertItem();
        alertBatch($item, 30);
        alertBaseline();

        alertIssue([[$item, 12]]); // 18: Reorder
        alertIssue([[$item, 15]]); // 3: Critical
        alertIssue([[$item, 3]]);  // 0: Out

        $alerts = alertsSentTo($this->custodian);
        expect($alerts)->toHaveCount(3)
            ->and($alerts->map(fn ($n) => [$n->items[0]['previous_status'], $n->items[0]['status']])->all())
            ->toBe([['healthy', 'reorder'], ['reorder', 'critical'], ['critical', 'out']])
            ->and($item->fresh()->stock_status)->toBe(StockStatus::Out);

        alertReceive($item, 4); // 4: Critical, an improvement

        expect(alertsSentTo($this->custodian))->toHaveCount(3)
            ->and($item->fresh()->stock_status)->toBe(StockStatus::Critical);
        Notification::assertSentTimes(StockStatusAlert::class, 6);
    });

    test('one issuance that worsens several items sends one alert per user listing all of them', function () {
        $items = collect(['Item Alpha', 'Item Bravo', 'Item Charlie'])->map(function (string $name) {
            $item = alertItem(['name' => $name]);
            alertBatch($item, 30);

            return $item;
        });
        alertBaseline();

        alertIssue($items->map(fn ($item) => [$item, 15])->all());

        Notification::assertSentTimes(StockStatusAlert::class, 2);

        foreach ([$this->custodian, $this->colleague] as $user) {
            $alerts = alertsSentTo($user);
            expect($alerts)->toHaveCount(1)
                ->and(collect($alerts[0]->items)->pluck('name')->all())->toBe(['Item Alpha', 'Item Bravo', 'Item Charlie'])
                ->and(collect($alerts[0]->items)->pluck('status')->unique()->all())->toBe(['reorder'])
                ->and($alerts[0]->subject())->toBe('Stock alert: 3 items need attention');
        }
    });

    test('a movement that keeps the same status sends nothing', function () {
        $item = alertItem();
        alertBatch($item, 50);
        alertBaseline();

        alertIssue([[$item, 5]]); // 45: still Healthy

        Notification::assertSentTimes(StockStatusAlert::class, 0);
        expect($item->fresh()->stock_status)->toBe(StockStatus::Healthy);
    });

    test('an inactive item never alerts', function () {
        $item = alertItem(['is_active' => false]);
        $batch = alertBatch($item, 30);
        alertBaseline();

        expect($item->fresh()->stock_status)->toBe(StockStatus::Healthy);

        Livewire::test(AdjustmentCreateForm::class)
            ->set('form.type', AdjustmentType::Damage->value)
            ->set('form.batch_id', $batch->id)
            ->set('form.quantity', 28)
            ->set('form.reason', 'Water damage')
            ->set('form.approved_by', 'Mark Supervisor')
            ->call('save')
            ->assertHasNoErrors();

        expect($batch->fresh()->remaining_qty)->toBe(2);

        $this->artisan('inventory:check-stock-status')->assertSuccessful();

        Notification::assertSentTimes(StockStatusAlert::class, 0);
    });

    test('raising the reorder level above the available quantity alerts', function () {
        $item = alertItem(['name' => 'Ballpen Black']);
        alertBatch($item, 30);
        alertBaseline();

        Livewire::test(ItemEditForm::class)
            ->call('edit', $item->id)
            ->set('form.reorder_level', 40)
            ->call('save')
            ->assertHasNoErrors();

        Notification::assertSentTimes(StockStatusAlert::class, 2);

        $alert = alertsSentTo($this->custodian)->sole();
        expect($alert->items[0]['name'])->toBe('Ballpen Black')
            ->and($alert->items[0]['previous_status'])->toBe('healthy')
            ->and($alert->items[0]['status'])->toBe('reorder')
            ->and($alert->items[0]['reorder_level'])->toBe(40)
            ->and($item->fresh()->stock_status)->toBe(StockStatus::Reorder);
    });

    test('the daily check alerts once when a batch expires', function () {
        $item = alertItem(['name' => 'Alcohol 70%', 'requires_expiry' => true]);
        alertBatch($item, 30, today()->addDay());
        alertBaseline();

        expect($item->fresh()->stock_status)->toBe(StockStatus::Healthy);

        $this->travel(3)->days();

        $this->artisan('inventory:check-stock-status')->assertSuccessful();

        Notification::assertSentTimes(StockStatusAlert::class, 2);
        $alert = alertsSentTo($this->custodian)->sole();
        expect($alert->items[0]['name'])->toBe('Alcohol 70%')
            ->and($alert->items[0]['previous_status'])->toBe('healthy')
            ->and($alert->items[0]['status'])->toBe('out')
            ->and($item->fresh()->stock_status)->toBe(StockStatus::Out);

        $this->artisan('inventory:check-stock-status')->assertSuccessful();

        Notification::assertSentTimes(StockStatusAlert::class, 2);
    });

    test('the alert mail lists the items, their statuses and links to stock monitoring', function () {
        $paper = alertItem(['name' => 'A4 Copy Paper', 'item_code' => 'INV-0001']);
        $paper->setAttribute('previous_status', StockStatus::Healthy);
        $paper->setAttribute('stock_status', StockStatus::Reorder);
        $paper->setAttribute('available_qty', 15);

        $alcohol = alertItem(['name' => 'Alcohol 70%', 'item_code' => 'INV-0002']);
        $alcohol->setAttribute('previous_status', StockStatus::Critical);
        $alcohol->setAttribute('stock_status', StockStatus::Out);
        $alcohol->setAttribute('available_qty', 0);

        $several = new StockStatusAlert(collect([$paper, $alcohol]));
        $html = (string) $several->toMail($this->custodian)->render();

        expect($html)->toContain('A4 Copy Paper')
            ->toContain('Alcohol 70%')
            ->toContain('INV-0001')
            ->toContain('Healthy')
            ->toContain('Reorder')
            ->toContain('Critical')
            ->toContain('Out of stock')
            ->toContain(route('monitoring.stock-monitoring'))
            ->and($several->subject())->toBe('Stock alert: 2 items need attention')
            ->and($several->toMail($this->custodian)->subject)->toBe('Stock alert: 2 items need attention');

        $single = new StockStatusAlert(collect([$paper]));

        expect($single->subject())->toBe('Stock alert: A4 Copy Paper is Reorder')
            ->and($single->via($this->custodian))->toBe(['mail']);
    });
});
