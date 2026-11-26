<?php

use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\Approvals as ApprovalsPage;
use App\Livewire\Requisitions\Issuances;
use App\Livewire\Requisitions\Requests;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Notifications\RequisitionStatusUpdated;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();

    $this->staff = User::factory()->create(['name' => 'Staff Member']);
    $this->actingAs($this->staff);

    InventoryCategories::factory()->create();
    $receipt = StockReceipts::create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->staff->id,
        'reference_number' => 'DR-001',
        'received_date' => today(),
        'delivery_date' => now(),
        'status' => 'complete_delivery',
    ]);

    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'requires_expiry' => false, 'is_active' => true]);
    InventoryBatches::create([
        'inventory_item_id' => $this->paper->id,
        'stock_receipt_id' => $receipt->id,
        'storage_location_id' => StorageLocations::factory()->create()->id,
        'batch_number' => 'P-1',
        'received_qty' => 50,
        'remaining_qty' => 50,
        'received_at' => now(),
        'unit_cost' => 10,
    ]);
});

function emailRequest(RequisitionStatus $status = RequisitionStatus::Pending, ?string $email = 'req@gmail.com', ?User $owner = null): Requisitions
{
    static $n = 0;
    $n++;

    $approved = $status === RequisitionStatus::Approved;

    $requisition = Requisitions::create([
        'request_number' => sprintf('REQ-MAIL-%04d', $n),
        'user_id' => $owner?->id,
        'requester_name' => 'Juan Dela Cruz',
        'requester_email' => $email,
        'status' => $status,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now(),
    ]);

    $requisition->items()->create([
        'inventory_item_id' => test()->paper->id,
        'requested_qty' => 10,
        'available_qty' => 10,
        'approved_qty' => $approved ? 10 : null,
        'released_qty' => 0,
        'outstanding_qty' => $approved ? 10 : 0,
    ]);

    return $requisition->load('items');
}

function assertStatusMailSent(Requisitions $requisition, RequisitionStatus $status, string $email = 'req@gmail.com'): void
{
    Notification::assertSentOnDemandTimes(RequisitionStatusUpdated::class, 1);
    Notification::assertSentOnDemand(
        RequisitionStatusUpdated::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $email
            && $notification->requisition->is($requisition)
            && $notification->requisition->status === $status,
    );
}

test('approving sends exactly one status email', function () {
    $requisition = emailRequest();

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id)->call('approve')->assertHasNoErrors();

    assertStatusMailSent($requisition, RequisitionStatus::Approved);
});

test('rejecting sends exactly one status email', function () {
    $requisition = emailRequest();

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->set('form.remarks', 'Not in budget')
        ->call('reject')
        ->assertHasNoErrors();

    assertStatusMailSent($requisition, RequisitionStatus::Rejected);
});

test('a partial issuance sends one status email', function () {
    $requisition = emailRequest(RequisitionStatus::Approved);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 4)
        ->call('release')
        ->assertHasNoErrors();

    assertStatusMailSent($requisition, RequisitionStatus::PartiallyIssued);
});

test('a full issuance sends one status email', function () {
    $requisition = emailRequest(RequisitionStatus::Approved);

    Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    assertStatusMailSent($requisition, RequisitionStatus::Issued);
});

test('finishing a partially issued request sends a second, Issued email', function () {
    $requisition = emailRequest(RequisitionStatus::Approved);
    $line = $requisition->items->first();

    $page = Livewire::withoutLazyLoading()->test(Issuances::class)->call('open', $requisition->id);
    $page->set("form.release.{$line->id}", 4)->call('release')->assertHasNoErrors();
    $page->call('open', $requisition->id)->call('release')->assertHasNoErrors();

    Notification::assertSentOnDemandTimes(RequisitionStatusUpdated::class, 2);
    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Issued);
});

test('cancelling sends exactly one status email', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);
    $requisition = emailRequest(RequisitionStatus::Pending, 'req@gmail.com', $owner);

    Livewire::withoutLazyLoading()->test(Requests::class)->call('cancel', $requisition->id);

    assertStatusMailSent($requisition, RequisitionStatus::Cancelled);
});

test('no email is sent when the request has no email address', function () {
    $requisition = emailRequest(RequisitionStatus::Pending, null);

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id)->call('approve')->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
    Notification::assertNothingSent();
});

test('no email is sent when the status does not change', function () {
    $requisition = emailRequest();

    $requisition->update(['purpose' => 'Changed purpose']);
    $requisition->update(['status' => RequisitionStatus::Pending]);

    Notification::assertNothingSent();
});

test('no email is sent when an issuance fails and rolls back', function () {
    $requisition = emailRequest(RequisitionStatus::Approved);
    $line = $requisition->items->first();

    // More than the 50 on hand but within the outstanding qty: the allocator fails.
    $line->update(['approved_qty' => 80, 'outstanding_qty' => 80]);

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 80)
        ->call('release')
        ->assertHasErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
    Notification::assertNothingSent();
});

test('an invalid issuance quantity sends nothing', function () {
    $requisition = emailRequest(RequisitionStatus::Approved);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(Issuances::class)
        ->call('open', $requisition->id)
        ->set("form.release.{$line->id}", 11)
        ->call('release')
        ->assertHasErrors("form.release.{$line->id}");

    Notification::assertNothingSent();
});
