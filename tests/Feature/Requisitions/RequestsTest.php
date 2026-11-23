<?php

use App\Enums\RequisitionStatus;
use App\Livewire\Portal\RequestForm;
use App\Livewire\Requisitions\Requests;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\RequisitionItems;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Services\AdministrativeAreas\AdministrativeAreaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/** Same shape as the real administrative-areas API response. */
function fakeAdministrativeAreas(): void
{
    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);

    Http::fake([
        'areas.test/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 6, 'name' => 'Finance Office', 'alias' => 'Finance', 'abbreviation' => 'FO', 'type' => 'office', 'parent_id' => null],
                ['id' => 48, 'name' => 'Accounting Section', 'alias' => 'Accounting Section', 'abbreviation' => 'AS', 'type' => 'section', 'parent_id' => 6],
                ['id' => 65, 'name' => 'Admitting Unit', 'alias' => 'Admitting Unit', 'abbreviation' => 'AU', 'type' => 'unit', 'parent_id' => 3],
                ['id' => 3, 'name' => 'Allied Health Professional Service Division', 'alias' => 'Allied Office', 'abbreviation' => 'ALLIED', 'type' => 'office', 'parent_id' => null],
            ],
        ]),
    ]);
}

beforeEach(function () {
    fakeAdministrativeAreas();

    $this->user = User::factory()->create(['name' => 'Jane Clerk']);

    InventoryCategories::factory()->create();

    $receipt = StockReceipts::create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->user->id,
        'reference_number' => 'DR-001',
        'received_date' => today(),
        'status' => 'complete_delivery',
    ]);

    $location = StorageLocations::factory()->create();

    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true]);
    $this->pens = InventoryItems::factory()->create(['name' => 'Ballpoint Pen', 'is_active' => true]);

    foreach ([[30, null, 'BAT-KEEP-1'], [20, null, 'BAT-KEEP-2'], [99, today()->subDay(), 'BAT-OLD']] as [$qty, $expires, $batch]) {
        InventoryBatches::create([
            'inventory_item_id' => $this->paper->id,
            'stock_receipt_id' => $receipt->id,
            'storage_location_id' => $location->id,
            'batch_number' => $batch,
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'expiration_date' => $expires, // the 99 batch is expired, so not available
            'received_at' => now(),
            'unit_cost' => 10,
        ]);
    }
});

/**
 * Fill the public form, review it, and (unless $confirm is false) submit.
 *
 * @param  array<int, array{0: InventoryItems, 1: int}>  $lines
 */
function submitRequest(array $lines, string $purpose = 'Monthly supplies', bool $confirm = true)
{
    $component = Livewire::test(RequestForm::class)
        ->set('form.requester_name', 'Juan Dela Cruz')
        ->set('form.supervisor_name', 'Maria Santos')
        ->set('form.administrative_area_id', 48)
        ->set('form.purpose', $purpose);

    foreach (array_values($lines) as $i => [$item, $qty]) {
        if ($i > 0) {
            $component->call('addItem');
        }

        $component
            ->set("form.items.$i.inventory_item_id", $item->id)
            ->set("form.items.$i.requested_qty", $qty);
    }

    $component->call('review');

    return $confirm ? $component->call('submit') : $component;
}

test('the welcome page shows the request and tracking tabs to guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Submit a request')
        ->assertSee('Track a request')
        ->assertSee('Accounting Section (AS) · Section · Finance Office');
});

test('the staff requests page renders without a create button', function () {
    $this->actingAs($this->user)
        ->get(route('requisitions.requests'))
        ->assertOk()
        ->assertSee('Requisition Requests')
        ->assertDontSee('New Request');
});

test('a guest can submit a multi-item request and gets a tracking code', function () {
    $component = submitRequest([[$this->paper, 80], [$this->pens, 5]])
        ->assertHasNoErrors()
        ->assertSet('step', 'done');

    $requisition = Requisitions::sole();

    expect($requisition->status)->toBe(RequisitionStatus::Pending)
        ->and($requisition->user_id)->toBeNull()
        ->and($requisition->requester_name)->toBe('Juan Dela Cruz')
        ->and($requisition->supervisor_name)->toBe('Maria Santos')
        ->and($requisition->administrative_area_id)->toBe(48)
        ->and($requisition->designation)->toBe('Accounting Section (AS) · Section · Finance Office')
        ->and($requisition->purpose)->toBe('Monthly supplies')
        ->and($requisition->request_number)->toBe('REQ-'.now()->format('Ym').'-0001')
        ->and($requisition->tracking_code)->toMatch('/^TRK-[A-HJ-NP-Z2-9]{8}$/');

    $component->assertSet('trackingCode', $requisition->tracking_code)
        ->assertSee($requisition->tracking_code);

    $paper = $requisition->items()->where('inventory_item_id', $this->paper->id)->sole();
    expect($paper->requested_qty)->toBe(80)
        ->and($paper->available_qty)->toBe(50)
        ->and($paper->approved_qty)->toBeNull()
        ->and($paper->released_qty)->toBe(0)
        ->and($paper->outstanding_qty)->toBe(0);

    expect($requisition->items()->where('inventory_item_id', $this->pens->id)->sole()->available_qty)->toBe(0);
});

test('the review step shows a summary and saves nothing until confirmed', function () {
    submitRequest([[$this->paper, 80], [$this->pens, 5]], confirm: false)
        ->assertHasNoErrors()
        ->assertSet('step', 'review')
        ->assertSeeInOrder(['Juan Dela Cruz', 'Maria Santos', 'Accounting Section', 'Monthly supplies'])
        ->assertSeeInOrder(['A4 Copy Paper', '80', '50', 'Ballpoint Pen', '5', '0'])
        ->call('edit')
        ->assertSet('step', 'form')
        ->assertSet('form.items.0.requested_qty', 80);

    expect(Requisitions::count())->toBe(0);
});

test('item choices show available totals, not batches', function () {
    Livewire::test(RequestForm::class)
        ->assertSee('A4 Copy Paper')
        ->assertSee('(50')
        ->assertDontSee('BAT-KEEP-1')
        ->assertDontSee('BAT-OLD');
});

test('submitting a request never touches stock', function () {
    submitRequest([[$this->paper, 10]])->assertHasNoErrors();

    expect((int) InventoryBatches::sum('remaining_qty'))->toBe(149)
        ->and(InventoryTransactions::count())->toBe(0);
});

test('request numbers increment and tracking codes are unique', function () {
    submitRequest([[$this->paper, 1]]);
    submitRequest([[$this->paper, 2]]);

    expect(Requisitions::orderBy('id')->pluck('request_number')->all())->toBe([
        'REQ-'.now()->format('Ym').'-0001',
        'REQ-'.now()->format('Ym').'-0002',
    ])->and(Requisitions::distinct()->count('tracking_code'))->toBe(2);
});

test('a request needs names, a designation, a purpose and valid lines', function () {
    Livewire::test(RequestForm::class)
        ->set('form.items.0.requested_qty', 0)
        ->call('review')
        ->assertHasErrors([
            'form.requester_name' => 'required',
            'form.supervisor_name' => 'required',
            'form.administrative_area_id' => 'required',
            'form.purpose' => 'required',
            'form.items.0.inventory_item_id' => 'required',
            'form.items.0.requested_qty' => 'min',
        ])
        ->assertSet('step', 'form');

    expect(Requisitions::count())->toBe(0);
});

test('an unknown designation is rejected', function () {
    Livewire::test(RequestForm::class)
        ->set('form.administrative_area_id', 9999)
        ->call('review')
        ->assertHasErrors(['form.administrative_area_id']);
});

test('administrative areas survive a round trip through the cache', function () {
    // The first call fetches and caches; the second reads the cache back.
    // Regression: a cached Collection came back as __PHP_Incomplete_Class
    // because config/cache.php sets serializable_classes = false.
    // The array store used in tests doesn't enforce that setting; a real
    // serializing store (file, like database in prod) does.
    config(['cache.default' => 'file', 'cache.stores.file.path' => storage_path('framework/testing/cache')]);
    cache()->flush();
    $areas = app(AdministrativeAreaService::class);

    $areas->all();
    $cached = $areas->all();

    expect($cached)->toBeInstanceOf(Collection::class)
        ->and($cached)->toHaveCount(4)
        ->and($areas->find(48)['name'])->toBe('Accounting Section');
    Http::assertSentCount(1);
});

test('the form explains when designations cannot be loaded', function () {
    // Point at a host whose fake fails (the beforeEach fake would match areas.test first).
    config(['services.administrative_areas.url' => 'https://down.test/api/administrative-areas']);
    Http::fake(['down.test/*' => Http::response([], 500)]);
    cache()->flush();

    Livewire::test(RequestForm::class)
        ->assertSee('Designations could not be loaded');
});

test('the same item cannot be on two lines', function () {
    submitRequest([[$this->paper, 1], [$this->paper, 2]])
        ->assertHasErrors(['form.items.1.inventory_item_id' => 'distinct']);
});

test('inactive items cannot be requested', function () {
    $this->pens->update(['is_active' => false]);

    submitRequest([[$this->pens, 1]])->assertHasErrors(['form.items.0.inventory_item_id' => 'exists']);
});

test('public submissions are rate limited', function () {
    RateLimiter::clear('public-request:127.0.0.1');

    foreach (range(1, RequestForm::MAX_SUBMISSIONS) as $n) {
        submitRequest([[$this->paper, $n]])->assertHasNoErrors();
    }

    submitRequest([[$this->paper, 1]])->assertHasErrors('submit');

    expect(Requisitions::count())->toBe(RequestForm::MAX_SUBMISSIONS);
});

test('a signed-in requester can cancel their own pending request', function () {
    $this->actingAs($this->user);
    submitRequest([[$this->paper, 5]]);
    $requisition = Requisitions::sole();

    expect($requisition->user_id)->toBe($this->user->id);

    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    $requisition->refresh();
    expect($requisition->status)->toBe(RequisitionStatus::Cancelled)
        ->and($requisition->cancelled_at)->not->toBeNull();
});

test('a request cannot be cancelled once it has been acted on', function () {
    $this->actingAs($this->user);
    submitRequest([[$this->paper, 5]]);
    $requisition = Requisitions::sole();
    $requisition->update(['status' => RequisitionStatus::Approved]);

    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
});

test('staff cannot cancel public requests or ones made by someone else', function () {
    submitRequest([[$this->paper, 5]]); // as a guest
    $requisition = Requisitions::sole();

    $this->actingAs(User::factory()->create());
    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
});

test('the staff list shows every request, including public ones', function () {
    submitRequest([[$this->paper, 5]], 'From the public');

    $this->actingAs($this->user);
    submitRequest([[$this->paper, 5]], 'From staff');

    Livewire::test(Requests::class)
        ->assertSee('From the public')
        ->assertSee('From staff')
        ->assertSee('Juan Dela Cruz');
});

test('the list can be searched by item, requester and tracking code, and filtered by status', function () {
    submitRequest([[$this->paper, 5]], 'Paper run');
    submitRequest([[$this->pens, 5]], 'Pen run');
    Requisitions::where('purpose', 'Pen run')->update(['status' => RequisitionStatus::Rejected, 'requester_name' => 'Pedro Penn']);
    $paperCode = Requisitions::where('purpose', 'Paper run')->value('tracking_code');

    $this->actingAs($this->user);

    Livewire::test(Requests::class)
        ->set('search', 'Ballpoint')
        ->assertSee('Pen run')
        ->assertDontSee('Paper run')
        ->set('search', 'Pedro')
        ->assertSee('Pen run')
        ->assertDontSee('Paper run')
        ->set('search', $paperCode)
        ->assertSee('Paper run')
        ->assertDontSee('Pen run')
        ->set('search', '')
        ->set('status', RequisitionStatus::Pending->value)
        ->assertSee('Paper run')
        ->assertDontSee('Pen run');
});

test('viewing a request shows the requester details and every quantity stage', function () {
    submitRequest([[$this->paper, 80]]);
    $requisition = Requisitions::sole();
    RequisitionItems::sole()->update(['approved_qty' => 40, 'released_qty' => 15, 'outstanding_qty' => 25]);

    $this->actingAs($this->user);

    Livewire::test(Requests::class)
        ->call('view', $requisition->id)
        ->assertSet('viewingId', $requisition->id)
        ->assertSeeInOrder(['Juan Dela Cruz', $requisition->tracking_code, 'Maria Santos', 'Accounting Section'])
        ->assertSeeInOrder(['A4 Copy Paper', '80', '50', '40', '15', '25']);
});
