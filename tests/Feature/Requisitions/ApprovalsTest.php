<?php

use App\Enums\ApprovalDecision;
use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\Approvals as ApprovalsPage;
use App\Models\Approvals;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->requester = User::factory()->create(['name' => 'Jane Requester']);
    $this->approver = User::factory()->create(['name' => 'Mark Approver']);
    $this->actingAs($this->approver);

    InventoryCategories::factory()->create();

    $receipt = StockReceipts::create([
        'supplier_id' => Suppliers::factory()->create()->id,
        'received_by' => $this->approver->id,
        'reference_number' => 'DR-001',
        'received_date' => today(),
        'delivery_date' => now(),
        'status' => 'complete_delivery',
    ]);

    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true]);
    $this->pens = InventoryItems::factory()->create(['name' => 'Ballpoint Pen', 'is_active' => true]);

    $location = StorageLocations::factory()->create();

    foreach ([[$this->paper, 50], [$this->pens, 10]] as [$item, $qty]) {
        InventoryBatches::create([
            'inventory_item_id' => $item->id,
            'stock_receipt_id' => $receipt->id,
            'storage_location_id' => $location->id,
            'received_qty' => $qty,
            'remaining_qty' => $qty,
            'received_at' => now(),
            'unit_cost' => 10,
        ]);
    }
});

/**
 * @param  array<int, array{0: InventoryItems, 1: int}>  $lines
 */
function pendingRequest(array $lines, ?User $by = null, string $purpose = 'Monthly supplies'): Requisitions
{
    static $n = 0;
    $n++;

    $requisition = Requisitions::create([
        'request_number' => sprintf('REQ-TEST-%04d', $n),
        'user_id' => ($by ?? test()->requester)->id,
        'status' => RequisitionStatus::Pending,
        'purpose' => $purpose,
        'submitted_at' => now(),
    ]);

    foreach ($lines as [$item, $qty]) {
        $requisition->items()->create([
            'inventory_item_id' => $item->id,
            'requested_qty' => $qty,
            'available_qty' => 0,
            'approved_qty' => null,
        ]);
    }

    return $requisition->load('items');
}

test('the approvals page renders', function () {
    $this->get(route('requisitions.approvals'))->assertOk()->assertSee('Requisition Approvals');
});

test('the queue lists pending requests only by default', function () {
    pendingRequest([[$this->paper, 1]], purpose: 'Waiting one');
    pendingRequest([[$this->paper, 1]], purpose: 'Done one')->update(['status' => RequisitionStatus::Approved]);

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->assertSee('Waiting one')
        ->assertDontSee('Done one')
        ->set('status', '')
        ->assertSee('Done one');
});

test('reviewing pre-fills approved quantities capped by free stock', function () {
    $requisition = pendingRequest([[$this->paper, 20], [$this->pens, 15]]);
    [$paper, $pens] = $requisition->items;

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->assertSet("form.approved.{$paper->id}", 20)
        ->assertSet("form.approved.{$pens->id}", 10);
});

test('approving records the decision and keeps every quantity', function () {
    $requisition = pendingRequest([[$this->paper, 20], [$this->pens, 15]]);
    [$paper, $pens] = $requisition->items;

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->set("form.approved.{$paper->id}", 12)
        ->set("form.approved.{$pens->id}", 0)
        ->set('form.remarks', 'Pens next month')
        ->call('approve')
        ->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Approved);

    $paper->refresh();
    expect($paper->requested_qty)->toBe(20)
        ->and($paper->approved_qty)->toBe(12)
        ->and($paper->released_qty)->toBe(0)
        ->and($paper->outstanding_qty)->toBe(12);

    expect($pens->fresh()->approved_qty)->toBe(0)
        ->and($pens->fresh()->outstanding_qty)->toBe(0);

    $approval = Approvals::sole();
    expect($approval->decision)->toBe(ApprovalDecision::Approved)
        ->and($approval->user_id)->toBe($this->approver->id)
        ->and($approval->remarks)->toBe('Pens next month')
        ->and($approval->decided_at)->not->toBeNull();
});

test('approval never moves stock', function () {
    $requisition = pendingRequest([[$this->paper, 20]]);

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id)->call('approve');

    expect((int) InventoryBatches::sum('remaining_qty'))->toBe(60)
        ->and(InventoryTransactions::count())->toBe(0);
});

test('cannot approve more than was requested', function () {
    $requisition = pendingRequest([[$this->paper, 5]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->set("form.approved.{$line->id}", 6)
        ->call('approve')
        ->assertHasErrors("form.approved.{$line->id}");

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
});

test('stock already committed to another request cannot be approved twice', function () {
    $first = pendingRequest([[$this->paper, 40]]);
    $second = pendingRequest([[$this->paper, 40]]);

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $first->id)->call('approve')->assertHasNoErrors();

    $line = $second->items->first();

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $second->id)
        ->assertSet("form.approved.{$line->id}", 10) // 50 on hand - 40 committed
        ->set("form.approved.{$line->id}", 11)
        ->call('approve')
        ->assertHasErrors("form.approved.{$line->id}");
});

test('approving nothing at all is refused', function () {
    $requisition = pendingRequest([[$this->paper, 5]]);
    $line = $requisition->items->first();

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->set("form.approved.{$line->id}", 0)
        ->call('approve')
        ->assertHasErrors('form.approved');
});

test('rejecting requires remarks and records the decision', function () {
    $requisition = pendingRequest([[$this->paper, 5]]);

    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)
        ->call('review', $requisition->id)
        ->call('reject')
        ->assertHasErrors('form.remarks')
        ->set('form.remarks', 'Not in budget')
        ->call('reject')
        ->assertHasNoErrors();

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Rejected)
        ->and($requisition->items->first()->fresh()->approved_qty)->toBe(0)
        ->and(Approvals::sole()->decision)->toBe(ApprovalDecision::Rejected)
        ->and(Approvals::sole()->remarks)->toBe('Not in budget');
});

test('a request that is no longer pending cannot be decided', function () {
    $requisition = pendingRequest([[$this->paper, 5]]);

    $page = Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id);

    // The requester cancels while the approver has the request open.
    $requisition->update(['status' => RequisitionStatus::Cancelled]);

    $page->call('approve')->assertHasErrors('form.requisition_id');

    expect($requisition->fresh()->status)->toBe(RequisitionStatus::Cancelled)
        ->and(Approvals::count())->toBe(0);
});

test('approval decisions are permanent', function () {
    $requisition = pendingRequest([[$this->paper, 5]]);
    Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id)->call('approve');

    expect(fn () => Approvals::sole()->update(['remarks' => 'changed']))->toThrow(LogicException::class)
        ->and(fn () => Approvals::sole()->delete())->toThrow(LogicException::class);
});
