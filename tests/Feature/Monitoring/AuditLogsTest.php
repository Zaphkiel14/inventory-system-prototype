<?php

use App\Enums\AuditAction;
use App\Enums\PropertyClass;
use App\Livewire\Monitoring\AuditLogs as AuditLogsPage;
use App\Models\AuditLogs;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\Requisitions;
use App\Models\User;
use Livewire\Livewire;

/**
 * Five audit rows, oldest first:
 *  a 2026-11-01 Alice  created  ICS SPLV-2026-11-001
 *  b 2026-11-01 Alice  created  line LV2026-0001 on that ICS
 *  c 2026-11-10 Bob    created  PAR 2026-11-003
 *  d 2026-11-10 Bob    RIS pattern override on RIS 26102455 ("Legacy logbook number")
 *  e 2026-11-20 Alice  status changed on the ICS ("Unit returned by the ER")
 *
 * @return array{alice: User, bob: User}
 */
function auditFixture(): array
{
    $alice = User::factory()->create(['name' => 'Alice Custodian']);
    $bob = User::factory()->create(['name' => 'Bob Approver']);

    test()->travelTo('2026-11-01 09:00:00');
    test()->actingAs($alice);
    $ics = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-001']);
    PropertyDocumentLines::factory()->for($ics, 'document')->create(['property_number' => 'LV2026-0001']);

    test()->travelTo('2026-11-10 09:00:00');
    test()->actingAs($bob);
    PropertyDocuments::factory()->par()->finalized()->create(['document_number' => '2026-11-003']);
    $requisition = Requisitions::factory()->approved()->create(['ris_number' => '26102455']);
    AuditLogs::record(AuditAction::RisPatternOverride, $requisition, [], ['ris_number' => '26102455'], 'Legacy logbook number');

    test()->travelTo('2026-11-20 09:00:00');
    test()->actingAs($alice);
    AuditLogs::record(AuditAction::StatusChanged, $ics, ['status' => 'active'], ['status' => 'returned'], 'Unit returned by the ER');

    return ['alice' => $alice, 'bob' => $bob];
}

it('lists audit rows newest first and narrows them with each filter', function (string $filter, Closure $value, array $expected) {
    $users = auditFixture();

    $logs = Livewire::test(AuditLogsPage::class)
        ->set($filter, $value($users))
        ->assertHasNoErrors()
        ->instance()
        ->logs;

    expect($logs->getCollection()->map(fn (AuditLogs $log) => $log->recordLabel())->all())->toBe($expected);
})->with([
    'no filter' => ['search', fn () => '', ['ICS SPLV-2026-11-001', 'RIS 26102455', 'PAR 2026-11-003', 'Line LV2026-0001 on ICS SPLV-2026-11-001', 'ICS SPLV-2026-11-001']],
    'action' => ['action', fn () => 'status_changed', ['ICS SPLV-2026-11-001']],
    'ICS records and their lines' => ['auditableType', fn () => 'ics', ['ICS SPLV-2026-11-001', 'Line LV2026-0001 on ICS SPLV-2026-11-001', 'ICS SPLV-2026-11-001']],
    'PAR records' => ['auditableType', fn () => 'par', ['PAR 2026-11-003']],
    'requisitions' => ['auditableType', fn () => 'requisition', ['RIS 26102455']],
    'from a date' => ['from', fn () => '2026-11-10', ['ICS SPLV-2026-11-001', 'RIS 26102455', 'PAR 2026-11-003']],
    'up to a date' => ['to', fn () => '2026-11-01', ['Line LV2026-0001 on ICS SPLV-2026-11-001', 'ICS SPLV-2026-11-001']],
    'user' => ['user', fn (array $users) => (string) $users['bob']->id, ['RIS 26102455', 'PAR 2026-11-003']],
    'search by document number' => ['search', fn () => 'SPLV-2026-11', ['ICS SPLV-2026-11-001', 'Line LV2026-0001 on ICS SPLV-2026-11-001', 'ICS SPLV-2026-11-001']],
    'search by property number' => ['search', fn () => 'LV2026-0001', ['Line LV2026-0001 on ICS SPLV-2026-11-001']],
    'search by RIS number' => ['search', fn () => '26102455', ['RIS 26102455']],
    'search by user name' => ['search', fn () => 'Bob', ['RIS 26102455', 'PAR 2026-11-003']],
    'search by reason' => ['search', fn () => 'logbook', ['RIS 26102455']],
]);

it('combines a date range with a record type', function () {
    auditFixture();

    $logs = Livewire::test(AuditLogsPage::class)
        ->set('from', '2026-11-01')
        ->set('to', '2026-11-10')
        ->set('auditableType', 'ics')
        ->instance()
        ->logs;

    expect($logs->getCollection()->map(fn (AuditLogs $log) => $log->recordLabel())->all())
        ->toBe(['Line LV2026-0001 on ICS SPLV-2026-11-001', 'ICS SPLV-2026-11-001']);
});

it('labels each audited record readably', function (Closure $arrange, Closure $expected) {
    $this->actingAs(User::factory()->create());
    $record = $arrange();

    expect(AuditLogs::query()->latest('id')->first()->recordLabel())->toBe($expected($record));
})->with([
    'a finalized ICS' => [
        fn () => PropertyDocuments::factory()->ics(PropertyClass::Sphv)->finalized()->create(['document_number' => 'SPHV-2026-11-004']),
        fn () => 'ICS SPHV-2026-11-004',
    ],
    'a draft ICS' => [
        fn () => PropertyDocuments::factory()->ics(PropertyClass::Splv)->create(),
        fn (PropertyDocuments $document) => "ICS draft #{$document->id}",
    ],
    'a PAR' => [
        fn () => PropertyDocuments::factory()->par()->finalized()->create(['document_number' => '2026-11-003']),
        fn () => 'PAR 2026-11-003',
    ],
    'a line on an ICS' => [
        fn () => PropertyDocumentLines::factory()
            ->for(PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-001']), 'document')
            ->create(['property_number' => 'LV2026-0001']),
        fn () => 'Line LV2026-0001 on ICS SPLV-2026-11-001',
    ],
    'a RIS' => [
        fn () => AuditLogs::record(AuditAction::RisPatternOverride, Requisitions::factory()->approved()->create(['ris_number' => '26102455']), [], [], 'Legacy number'),
        fn () => 'RIS 26102455',
    ],
    'a deleted document' => [
        fn () => tap(PropertyDocuments::factory()->par()->create(['document_number' => '2026-11-009']))->delete(),
        fn () => 'PAR 2026-11-009 (deleted)',
    ],
    'a deleted line' => [
        fn () => tap(PropertyDocumentLines::factory()->create(['property_number' => 'LV2026-0002']))->delete(),
        fn () => 'Line LV2026-0002 (deleted)',
    ],
]);

it('lists every touched field with its old and new value', function () {
    $this->actingAs(User::factory()->create());
    $document = PropertyDocuments::factory()->ics(PropertyClass::Splv)->create();

    $log = AuditLogs::record(AuditAction::Updated, $document, ['to_name' => 'Jane', 'remarks' => 'old'], ['to_name' => 'Juan', 'fund_cluster' => '01']);

    expect($log->changes())->toBe([
        'to_name' => ['Jane', 'Juan'],
        'remarks' => ['old', null],
        'fund_cluster' => [null, '01'],
    ]);
});

it('refuses to change or delete an audit row', function () {
    $this->actingAs(User::factory()->create());
    PropertyDocuments::factory()->ics(PropertyClass::Splv)->create();
    $log = AuditLogs::sole();

    expect(fn () => $log->update(['reason' => 'tampered']))->toThrow(LogicException::class, 'Audit logs are permanent and cannot be modified.')
        ->and(fn () => $log->delete())->toThrow(LogicException::class, 'Audit logs are permanent and cannot be deleted.');

    expect($log->fresh()->reason)->toBeNull();
});

it('records no user for a change made without a signed-in user', function () {
    PropertyDocuments::factory()->ics(PropertyClass::Splv)->create();

    expect(AuditLogs::sole())
        ->action->toBe(AuditAction::Created)
        ->user_id->toBeNull();
});
