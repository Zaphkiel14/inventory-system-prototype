<?php

use App\Enums\AuditAction;
use App\Enums\RequisitionStatus;
use App\Livewire\Requisitions\Approvals as ApprovalsPage;
use App\Models\Approvals;
use App\Models\AuditLogs;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->approver = User::factory()->create(['name' => 'Mark Approver']);
    $this->actingAs($this->approver);
    fakeAreaHierarchy();
    $this->travelTo('2026-11-27 09:00:00');

    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'requires_expiry' => false, 'is_active' => true]);
    InventoryBatches::factory()->create([
        'inventory_item_id' => $this->paper->id,
        'received_qty' => 50,
        'remaining_qty' => 50,
        'expiration_date' => null,
    ]);
});

/**
 * A pending request for 5 reams, open in the review modal.
 *
 * @param  array<string, mixed>  $attributes
 */
function reviewing(array $attributes = []): Testable
{
    $requisition = Requisitions::factory()->pending()
        ->withLine(test()->paper, 5)
        ->create(['requester_name' => 'Jane Requester', ...$attributes]);

    test()->requisition = $requisition;

    return Livewire::withoutLazyLoading()->test(ApprovalsPage::class)->call('review', $requisition->id);
}

it('saves the RIS header on approval without an audit entry when the RIS No. matches the pattern', function () {
    reviewing()
        ->assertSet('form.approved_by_name', 'Mark Approver')
        ->assertSet('form.approved_on', '2026-11-27')
        ->set('form.ris_number', ' 26102455 ')
        ->set('form.fund_cluster', ' 01 - Regular Agency Fund ')
        ->set('form.approved_by_designation', 'Chief Administrative Officer')
        ->call('approve')
        ->assertHasNoErrors();

    $requisition = $this->requisition->fresh();
    expect($requisition->status)->toBe(RequisitionStatus::Approved)
        ->and($requisition->ris_number)->toBe('26102455')
        ->and($requisition->fund_cluster)->toBe('01 - Regular Agency Fund')
        ->and($requisition->approved_by_name)->toBe('Mark Approver')
        ->and($requisition->approved_by_designation)->toBe('Chief Administrative Officer')
        ->and($requisition->approved_on->toDateString())->toBe('2026-11-27')
        ->and($requisition->division_area_id)->toBeNull()
        ->and($requisition->office_area_id)->toBeNull()
        ->and($requisition->rc_code)->toBeNull();

    $this->assertDatabaseCount('audit_logs', 0);
});

it('requires the RIS No., fund cluster and approver designation', function () {
    reviewing()
        ->call('approve')
        ->assertHasErrors([
            'form.ris_number' => 'The RIS No. field is required.',
            'form.fund_cluster' => 'The fund cluster field is required.',
            'form.approved_by_designation' => 'The approved by (designation) field is required.',
        ]);

    expect($this->requisition->fresh()->status)->toBe(RequisitionStatus::Pending)
        ->and(Approvals::count())->toBe(0);
});

it('rejects a RIS No. already used by another requisition', function () {
    Requisitions::factory()->approved()->create(['ris_number' => '26102455']);

    fillRisHeader(reviewing())
        ->set('form.ris_number', '26102455')
        ->call('approve')
        ->assertHasErrors(['form.ris_number' => 'The RIS No. has already been taken.']);

    expect($this->requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
});

it('accepts the RIS No. the requisition already holds', function () {
    $page = reviewing(['ris_number' => '26102455'])->assertSet('form.ris_number', '26102455');

    fillRisHeader($page)
        ->set('form.ris_number', '26102455')
        ->call('approve')
        ->assertHasNoErrors();

    expect($this->requisition->fresh()->status)->toBe(RequisitionStatus::Approved);
});

it('blocks a RIS No. off the pattern until an override reason is given', function () {
    fillRisHeader(reviewing())
        ->set('form.ris_number', 'RIS-2026-11')
        ->assertSet('risPatternMismatch', true)
        ->call('approve')
        ->assertHasErrors(['form.ris_number' => 'The RIS No. does not match the expected format (^\d{8}$). Check it, or give an override reason to save it anyway.']);

    expect($this->requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
    $this->assertDatabaseCount('audit_logs', 0);
});

it('saves a RIS No. off the pattern with an override reason and logs it once', function () {
    fillRisHeader(reviewing())
        ->set('form.ris_number', 'RIS-2026-11')
        ->set('form.pattern_override_reason', '  Legacy number from the paper logbook  ')
        ->call('approve')
        ->assertHasNoErrors();

    expect($this->requisition->fresh()->ris_number)->toBe('RIS-2026-11');

    $log = AuditLogs::sole();
    expect($log->action)->toBe(AuditAction::RisPatternOverride)
        ->and($log->user_id)->toBe($this->approver->id)
        ->and($log->auditable->is($this->requisition))->toBeTrue()
        ->and($log->new_values)->toBe(['ris_number' => 'RIS-2026-11'])
        ->and($log->reason)->toBe('Legacy number from the paper logbook');
});

it('accepts any administrative area as division and office and snapshots their labels', function (int $divisionId, int $officeId, string $division, string $office) {
    fillRisHeader(reviewing())
        ->set('form.division_area_id', $divisionId)
        ->set('form.office_area_id', $officeId)
        ->call('approve')
        ->assertHasNoErrors();

    expect($this->requisition->fresh())
        ->division_area_id->toBe($divisionId)
        ->division->toBe($division)
        ->office_area_id->toBe($officeId)
        ->office->toBe($office);
})->with([
    'a division over a unit' => [10, 11, 'Nursing Service Division (NSD)', 'Emergency Unit (EU)'],
    'a section over a top-level office' => [48, 1, 'Accounting Section (AS)', 'Office of the Medical Center Chief (OMCC)'],
]);

it('rejects a division or office the areas API does not list', function (string $field, string $message) {
    fillRisHeader(reviewing())
        ->set("form.{$field}", 999)
        ->call('approve')
        ->assertHasErrors(["form.{$field}" => $message]);

    expect($this->requisition->fresh()->status)->toBe(RequisitionStatus::Pending);
})->with([
    'unknown division' => ['division_area_id', 'Select a valid division.'],
    'unknown office' => ['office_area_id', 'Select a valid office.'],
]);

it('pre-fills the RC Code from the latest other requisition for the same office', function () {
    Requisitions::factory()->approved()->create(['office_area_id' => 11, 'rc_code' => 'RC-OLD']);
    Requisitions::factory()->approved()->create(['office_area_id' => 11, 'rc_code' => 'RC-NEW']);
    Requisitions::factory()->approved()->create(['office_area_id' => 11, 'rc_code' => null]);
    Requisitions::factory()->approved()->create(['office_area_id' => 48, 'rc_code' => 'RC-ACCT']);

    reviewing(['office_area_id' => 11])
        ->assertSet('form.rc_code', 'RC-NEW')
        ->set('form.rc_code', '')
        ->set('form.office_area_id', 48)
        ->assertSet('form.rc_code', 'RC-ACCT');
});

it('leaves the RC Code empty when no other requisition for the office has one', function () {
    reviewing(['office_area_id' => 10])->assertSet('form.rc_code', '');
});

it('warns without blocking when the approver name matches the requester', function () {
    fillRisHeader(reviewing())
        ->assertSet('approverMatchesRequester', false)
        ->set('form.approved_by_name', '  JANE requester ')
        ->assertSet('approverMatchesRequester', true)
        ->call('approve')
        ->assertHasNoErrors();

    expect($this->requisition->fresh())
        ->status->toBe(RequisitionStatus::Approved)
        ->approved_by_name->toBe('JANE requester');
});
