<?php

use App\Enums\ApprovalDecision;
use App\Enums\RequisitionStatus;
use App\Livewire\Portal\RequestForm;
use App\Livewire\Portal\TrackRequest;
use App\Livewire\PublicPortal;
use App\Livewire\Requisitions\Issuances;
use App\Models\Approvals;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    InventoryCategories::factory()->create();
    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true]);

    $this->requisition = Requisitions::create([
        'request_number' => 'REQ-TEST-0001',
        'tracking_code' => 'TRK-ABCD2345',
        'requester_name' => 'Juan Dela Cruz',
        'supervisor_name' => 'Maria Santos',
        'designation' => 'Accounting Section (AS) · Section · Finance Office',
        'status' => RequisitionStatus::Pending,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now(),
    ]);

    $this->requisition->items()->create([
        'inventory_item_id' => $this->paper->id,
        'requested_qty' => 80,
        'available_qty' => 50,
        'approved_qty' => null,
        'released_qty' => 0,
        'outstanding_qty' => 0,
    ]);

    RateLimiter::clear('track-request:127.0.0.1');
});

test('tracking by code shows status and quantities', function () {
    Livewire::test(TrackRequest::class)
        ->set('code', ' trk-abcd2345 ') // forgiving about case and spaces
        ->call('lookup')
        ->assertHasNoErrors()
        ->assertSet('requisitionId', $this->requisition->id)
        ->assertSee('TRK-ABCD2345')
        ->assertSee(RequisitionStatus::Pending->label())
        ->assertSee('Juan Dela Cruz')
        ->assertSee('Accounting Section')
        ->assertSeeInOrder(['A4 Copy Paper', '80', 'Pending', '0']);
});

test('tracking reflects the approval decision and remarks', function () {
    $this->requisition->items()->update(['approved_qty' => 40, 'outstanding_qty' => 40]);
    $this->requisition->update(['status' => RequisitionStatus::Approved]);
    Approvals::create([
        'requisition_id' => $this->requisition->id,
        'user_id' => User::factory()->create(['name' => 'Staff Approver'])->id,
        'decision' => ApprovalDecision::Approved,
        'remarks' => 'Half now, rest next month.',
        'decided_at' => now(),
    ]);

    Livewire::test(TrackRequest::class)
        ->set('code', 'TRK-ABCD2345')
        ->call('lookup')
        ->assertSee(RequisitionStatus::Approved->label())
        ->assertSee('Half now, rest next month.')
        ->assertSeeInOrder(['A4 Copy Paper', '80', '40'])
        ->assertDontSee('Staff Approver'); // no staff names on the public page
});

test('issuing a public request pre-fills the requester as receiver', function () {
    $this->requisition->items()->update(['approved_qty' => 40, 'outstanding_qty' => 40]);
    $this->requisition->update(['status' => RequisitionStatus::Approved]);

    $form = Livewire::actingAs(User::factory()->create())
        ->test(Issuances::class)
        ->call('open', $this->requisition->id);

    $form->assertSet('form.received_by', 'Juan Dela Cruz');
});

test('an unknown code shows a not-found message', function () {
    Livewire::test(TrackRequest::class)
        ->set('code', 'TRK-NOPE0000')
        ->call('lookup')
        ->assertSet('requisitionId', null)
        ->assertSee('No request found for that tracking number')
        ->assertDontSee('Juan Dela Cruz');
});

test('the internal request number cannot be used to track', function () {
    Livewire::test(TrackRequest::class)
        ->set('code', 'REQ-TEST-0001')
        ->call('lookup')
        ->assertSet('requisitionId', null);
});

test('lookups are rate limited', function () {
    $component = Livewire::test(TrackRequest::class)->set('code', 'TRK-NOPE0000');

    foreach (range(1, TrackRequest::MAX_LOOKUPS) as $_) {
        $component->call('lookup')->assertHasNoErrors();
    }

    $component->call('lookup')->assertHasErrors('code');
});

test('"track this request" switches to the tracking tab and looks it up', function () {
    Livewire::test(RequestForm::class)
        ->set('trackingCode', 'TRK-ABCD2345')
        ->call('track')
        ->assertDispatched('track-request', code: 'TRK-ABCD2345');

    Livewire::test(PublicPortal::class)
        ->dispatch('track-request', code: 'TRK-ABCD2345')
        ->assertSet('tab', 'track');

    Livewire::test(TrackRequest::class)
        ->dispatch('track-request', code: 'TRK-ABCD2345')
        ->assertSet('requisitionId', $this->requisition->id);
});

test('an unknown tab falls back to the request form', function () {
    Livewire::withQueryParams(['tab' => 'bogus'])
        ->test(PublicPortal::class)
        ->assertSet('tab', 'request');
});
