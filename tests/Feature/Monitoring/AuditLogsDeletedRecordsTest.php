<?php

use App\Enums\AuditAction;
use App\Enums\PropertyClass;
use App\Livewire\Monitoring\AuditLogs as AuditLogsPage;
use App\Livewire\PropertyManagement\PropertyDocumentEditor;
use App\Models\AuditLogs;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\User;
use Livewire\Livewire;

/**
 * The audit trail keeps deleted drafts and their lines findable: type and
 * label are captured when each row is written, not looked up afterwards.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Alice Custodian']));

    $this->draft = PropertyDocuments::factory()->ics(PropertyClass::Sphv)->create(['document_number' => 'SPHV-2026-11-007']);
    PropertyDocumentLines::factory()->for($this->draft, 'document')->create(['property_number' => 'HV2026-0042']);

    // A PAR that stays, so the type filter has something to exclude.
    PropertyDocuments::factory()->par()->create(['document_number' => '2026-11-009']);

    Livewire::test(PropertyDocumentEditor::class)
        ->dispatch('edit-property-document', id: $this->draft->id)
        ->call('deleteDraft')
        ->assertHasNoErrors();
});

/**
 * @return list<string>
 */
function auditLabels(array $filters): array
{
    $page = Livewire::test(AuditLogsPage::class);

    foreach ($filters as $property => $value) {
        $page->set($property, $value);
    }

    return $page->get('logs')->getCollection()->map(fn (AuditLogs $log) => $log->recordLabel())->all();
}

it('captures the subject type and label when a row is written', function () {
    $deletedLine = AuditLogs::query()
        ->where('action', AuditAction::Deleted)
        ->where('auditable_type', (new PropertyDocumentLines)->getMorphClass())
        ->sole();

    expect($deletedLine->subject_type)->toBe('ics')
        ->and($deletedLine->subject_label)->toBe('Line HV2026-0042 on ICS SPHV-2026-11-007');
});

it('keeps a deleted draft and its lines under the ICS filter', function () {
    expect(PropertyDocuments::query()->whereKey($this->draft->id)->exists())->toBeFalse();

    expect(auditLabels(['auditableType' => 'ics']))->toBe([
        'ICS SPHV-2026-11-007 (deleted)',
        'Line HV2026-0042 (deleted)',
        'Line HV2026-0042 (deleted)',
        'ICS SPHV-2026-11-007 (deleted)',
    ]);

    expect(auditLabels(['auditableType' => 'par']))->toBe(['PAR 2026-11-009']);
});

it('finds a deleted draft and its lines by document number and property number', function () {
    expect(auditLabels(['search' => 'SPHV-2026-11-007']))->toHaveCount(4)
        ->and(auditLabels(['search' => 'HV2026-0042']))->toBe([
            'Line HV2026-0042 (deleted)',
            'Line HV2026-0042 (deleted)',
        ])
        ->and(auditLabels(['search' => 'SPHV-2026-11-007', 'action' => AuditAction::Deleted->value]))->toBe([
            'ICS SPHV-2026-11-007 (deleted)',
            'Line HV2026-0042 (deleted)',
        ]);
});

it('finds a line removed from a saved draft and records which document it was on', function () {
    $draft = PropertyDocuments::factory()->ics(PropertyClass::Splv)->create(['document_number' => 'SPLV-2026-11-020']);
    PropertyDocumentLines::factory()->count(2)->for($draft, 'document')->sequence(
        ['property_number' => 'LV2026-0101'],
        ['property_number' => 'LV2026-0102'],
    )->create();

    Livewire::test(PropertyDocumentEditor::class)
        ->dispatch('edit-property-document', id: $draft->id)
        ->call('removeLine', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(auditLabels(['search' => 'LV2026-0102', 'action' => AuditAction::Deleted->value]))
        ->toBe(['Line LV2026-0102 (deleted)']);

    expect(AuditLogs::query()->where('action', AuditAction::Deleted)->latest('id')->first()->subject_label)
        ->toBe('Line LV2026-0102 on ICS SPLV-2026-11-020');
});
