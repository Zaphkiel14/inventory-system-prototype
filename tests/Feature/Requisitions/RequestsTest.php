<?php

use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\RequestCreateForm;
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
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Jane Clerk']);
    $this->actingAs($this->user);

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

    foreach ([[30, null], [20, null], [99, today()->subDay()]] as [$qty, $expires]) {
        InventoryBatches::create([
            'inventory_item_id' => $this->paper->id,
            'stock_receipt_id' => $receipt->id,
            'storage_location_id' => $location->id,
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'expiration_date' => $expires, // the 99 batch is expired, so not available
            'received_at' => now(),
            'unit_cost' => 10,
        ]);
    }
});

/**
 * @param  array<int, array{0: InventoryItems, 1: int}>  $lines
 */
function submitRequest(array $lines, string $purpose = 'Monthly supplies')
{
    $component = Livewire::test(RequestCreateForm::class)->set('form.purpose', $purpose);

    foreach (array_values($lines) as $i => [$item, $qty]) {
        if ($i > 0) {
            $component->call('addItem');
        }

        $component
            ->set("form.items.$i.inventory_item_id", $item->id)
            ->set("form.items.$i.requested_qty", $qty);
    }

    return $component->call('save');
}

test('the requests page renders', function () {
    $this->get(route('requisitions.requests'))->assertOk()->assertSee('Requisition Requests');
});

test('submitting a request preserves requested and available quantities', function () {
    submitRequest([[$this->paper, 80], [$this->pens, 5]])
        ->assertHasNoErrors()
        ->assertDispatched('request-submitted');

    $requisition = Requisitions::sole();

    expect($requisition->status)->toBe(RequisitionStatus::Pending)
        ->and($requisition->user_id)->toBe($this->user->id)
        ->and($requisition->purpose)->toBe('Monthly supplies')
        ->and($requisition->submitted_at)->not->toBeNull()
        ->and($requisition->request_number)->toBe('REQ-'.now()->format('Ym').'-0001');

    $paper = $requisition->items()->where('inventory_item_id', $this->paper->id)->sole();
    expect($paper->requested_qty)->toBe(80)
        ->and($paper->available_qty)->toBe(50)
        ->and($paper->approved_qty)->toBeNull()
        ->and($paper->released_qty)->toBe(0)
        ->and($paper->outstanding_qty)->toBe(0);

    expect($requisition->items()->where('inventory_item_id', $this->pens->id)->sole()->available_qty)->toBe(0);
});

test('submitting a request never touches stock', function () {
    submitRequest([[$this->paper, 10]])->assertHasNoErrors();

    expect((int) InventoryBatches::sum('remaining_qty'))->toBe(149)
        ->and(InventoryTransactions::count())->toBe(0);
});

test('request numbers increment', function () {
    submitRequest([[$this->paper, 1]]);
    submitRequest([[$this->paper, 2]]);

    expect(Requisitions::orderBy('id')->pluck('request_number')->all())->toBe([
        'REQ-'.now()->format('Ym').'-0001',
        'REQ-'.now()->format('Ym').'-0002',
    ]);
});

test('a request needs a purpose and valid lines', function () {
    Livewire::test(RequestCreateForm::class)
        ->set('form.items.0.requested_qty', 0)
        ->call('save')
        ->assertHasErrors([
            'form.purpose' => 'required',
            'form.items.0.inventory_item_id' => 'required',
            'form.items.0.requested_qty' => 'min',
        ]);

    expect(Requisitions::count())->toBe(0);
});

test('the same item cannot be on two lines', function () {
    submitRequest([[$this->paper, 1], [$this->paper, 2]])
        ->assertHasErrors(['form.items.1.inventory_item_id' => 'distinct']);
});

test('inactive items cannot be requested', function () {
    $this->pens->update(['is_active' => false]);

    submitRequest([[$this->pens, 1]])->assertHasErrors(['form.items.0.inventory_item_id' => 'exists']);
});

test('a requester can cancel their own pending request', function () {
    submitRequest([[$this->paper, 5]]);
    $requisition = Requisitions::sole();

    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    $requisition->refresh();
    expect($requisition->status)->toBe(RequisitionStatus::Cancelled)
        ->and($requisition->cancelled_at)->not->toBeNull();
});

test('a request cannot be cancelled once it has been acted on', function () {
    submitRequest([[$this->paper, 5]]);
    $requisition = Requisitions::sole();
    $requisition->update(['status' => RequisitionStatus::Approved]);

    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
});

test('users cannot cancel requests made by someone else', function () {
    submitRequest([[$this->paper, 5]]);
    $requisition = Requisitions::sole();

    $this->actingAs(User::factory()->create());
    Livewire::test(Requests::class)->call('cancel', $requisition->id);

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
});

test('the list shows only my requests by default and everyone on demand', function () {
    submitRequest([[$this->paper, 5]], 'Mine');

    $this->actingAs(User::factory()->create());
    submitRequest([[$this->paper, 5]], 'Theirs');

    Livewire::test(Requests::class)
        ->assertSee('Theirs')
        ->assertDontSee('Mine')
        ->set('scope', 'all')
        ->assertSee('Mine')
        ->assertSee('Theirs');
});

test('the list can be searched by item and filtered by status', function () {
    submitRequest([[$this->paper, 5]], 'Paper run');
    submitRequest([[$this->pens, 5]], 'Pen run');
    Requisitions::where('purpose', 'Pen run')->update(['status' => RequisitionStatus::Rejected]);

    Livewire::test(Requests::class)
        ->set('search', 'Ballpoint')
        ->assertSee('Pen run')
        ->assertDontSee('Paper run')
        ->set('search', '')
        ->set('status', RequisitionStatus::Pending->value)
        ->assertSee('Paper run')
        ->assertDontSee('Pen run');
});

test('viewing a request shows every quantity stage', function () {
    submitRequest([[$this->paper, 80]]);
    $requisition = Requisitions::sole();
    RequisitionItems::sole()->update(['approved_qty' => 40, 'released_qty' => 15, 'outstanding_qty' => 25]);

    Livewire::test(Requests::class)
        ->call('view', $requisition->id)
        ->assertSet('viewingId', $requisition->id)
        ->assertSeeInOrder(['A4 Copy Paper', '80', '50', '40', '15', '25']);
});
