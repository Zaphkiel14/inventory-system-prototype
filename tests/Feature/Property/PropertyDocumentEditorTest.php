<?php

use App\Enums\AuditAction;
use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Events\PropertyDocumentFinalized;
use App\Livewire\PropertyManagement\PropertyDocumentEditor;
use App\Models\AuditLogs;
use App\Models\IssuedPropertyUnits;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\User;
use App\Support\PropertySettings;
use Illuminate\Support\Facades\Event;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Sam Custodian']));
    $this->travelTo('2026-11-27 09:00:00');
});

/**
 * The editor opened on a new hand-encoded document.
 */
function docEditor(string $type, ?string $icsClass = null): Testable
{
    return Livewire::test(PropertyDocumentEditor::class)->call('create', $type, $icsClass);
}

/**
 * Fill every header field finalize() requires.
 */
function docHeader(Testable $page, string $number): Testable
{
    return $page
        ->set('form.document_number', $number)
        ->set('form.fund_cluster', '01 - Regular Agency Fund')
        ->set('form.from_name', 'Sam Custodian')
        ->set('form.from_position', 'Supply Officer III')
        ->set('form.to_name', 'Jane Requester')
        ->set('form.to_position', 'Nurse II');
}

/**
 * Fill line $index with values finalize() accepts on an SPLV ICS, then apply $overrides.
 *
 * @param  array<string, mixed>  $overrides
 */
function docLine(Testable $page, int $index, array $overrides = []): Testable
{
    $values = [
        'item_name' => 'Electric Fan 16"',
        'unit' => 'unit',
        'unit_cost' => '1196.11',
        'quantity' => 1,
        'serial_number' => 'SN-000'.($index + 1),
        'property_number' => 'LV2026-000'.($index + 1),
        'estimated_useful_life_years' => 5,
        'date_acquired' => '2026-10-01',
        ...$overrides,
    ];

    foreach ($values as $field => $value) {
        $page->set("form.lines.{$index}.{$field}", $value);
    }

    return $page;
}

/**
 * A complete SPLV ICS with one line, finalized through the editor.
 */
function finalizedIcs(string $number = 'SPLV-2026-11-001'): PropertyDocuments
{
    docLine(docHeader(docEditor('ics', 'splv'), $number), 0)
        ->call('finalize')
        ->assertHasNoErrors();

    return PropertyDocuments::query()->where('document_number', $number)->sole();
}

/**
 * The editor opened on an existing document.
 */
function editDocument(PropertyDocuments $document): Testable
{
    return Livewire::test(PropertyDocumentEditor::class)->call('edit', $document->id);
}

describe('drafts', function () {
    it('saves a draft with only the line basics and stores the LV prefix as empty', function () {
        $page = docEditor('ics', 'splv')
            ->assertSet('form.lines.0.property_number', 'LV')
            ->set('form.lines.0.item_name', 'Electric Fan 16"')
            ->set('form.lines.0.unit', 'unit')
            ->set('form.lines.0.unit_cost', '1196.11')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('property-document-saved');

        $document = PropertyDocuments::sole();
        expect($document)
            ->status->toBe(PropertyDocumentStatus::Draft)
            ->ics_class->toBe(PropertyClass::Splv)
            ->document_number->toBeNull()
            ->finalized_at->toBeNull()
            ->and($document->lines()->sole()->only('item_name', 'unit_cost', 'quantity', 'property_number'))->toBe([
                'item_name' => 'Electric Fan 16"',
                'unit_cost' => '1196.11',
                'quantity' => 1,
                'property_number' => null,
            ]);

        editDocument($document)->assertSet('form.lines.0.property_number', 'LV');
    });

    it('pre-fills HV on an SPHV ICS and nothing on a PAR', function () {
        docEditor('ics', 'sphv')->assertSet('form.lines.0.property_number', 'HV');
        docEditor('par')->assertSet('form.lines.0.property_number', '');
    });

    it('requires the basics on every draft line', function (string $field, mixed $value, string $message) {
        docEditor('ics', 'splv')
            ->set('form.lines.0.item_name', 'Electric Fan 16"')
            ->set('form.lines.0.unit', 'unit')
            ->set('form.lines.0.unit_cost', '1196.11')
            ->set("form.lines.0.{$field}", $value)
            ->call('save')
            ->assertHasErrors(["form.lines.0.{$field}" => $message]);

        expect(PropertyDocuments::count())->toBe(0);
    })->with([
        'no description' => ['item_name', '', 'The description (line 1) field is required.'],
        'no unit' => ['unit', '', 'The unit (line 1) field is required.'],
        'no unit cost' => ['unit_cost', '', 'The unit cost (line 1) field is required.'],
        'a quantity of zero' => ['quantity', 0, 'The quantity (line 1) field must be at least 1.'],
    ]);

    it('requires at least one line', function () {
        docEditor('ics', 'splv')
            ->call('removeLine', 0)
            ->assertSet('form.lines', [])
            ->call('save')
            ->assertHasErrors(['form.lines' => 'The lines field is required.']);

        expect(PropertyDocuments::count())->toBe(0);
    });

    it('rejects a document number already used on the same type, even on a draft', function () {
        PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-001']);

        docLine(docEditor('ics', 'splv'), 0)
            ->set('form.document_number', 'SPLV-2026-11-001')
            ->call('save')
            ->assertHasErrors(['form.document_number' => 'The ICS No. has already been taken.']);
    });

    it('rejects a property number already on another document, ignoring case, even on a draft', function () {
        PropertyDocumentLines::factory()->create(['property_number' => 'LV2026-0001']);

        docLine(docEditor('ics', 'splv'), 0, ['property_number' => 'lv2026-0001'])
            ->call('save')
            ->assertHasErrors(['form.lines.0.property_number' => 'The inventory item no. (line 1) lv2026-0001 is already on another ICS or PAR.']);
    });
});

describe('finalize', function () {
    it('locks the document as Active with its renewal date, logs it once and fires the event', function () {
        Event::fake([PropertyDocumentFinalized::class]);

        $document = finalizedIcs();

        expect($document)
            ->status->toBe(PropertyDocumentStatus::Active)
            ->finalized_at->not->toBeNull()
            ->and($document->renewal_due_on->toDateString())->toBe('2029-11-27');

        expect(AuditLogs::query()->where('action', AuditAction::Finalized)->get()->map->only('auditable_id', 'auditable_type')->all())
            ->toBe([['auditable_id' => (string) $document->id, 'auditable_type' => $document->getMorphClass()]]);

        Event::assertDispatched(PropertyDocumentFinalized::class, fn (PropertyDocumentFinalized $event) => $event->document->is($document));
    });

    it('sets the renewal date from the renewal period setting', function () {
        PropertySettings::setRenewalYears(5);

        expect(finalizedIcs()->renewal_due_on->toDateString())->toBe('2031-11-27');
    });

    it('requires every header field before finalizing', function (string $field, string $message) {
        Event::fake([PropertyDocumentFinalized::class]);

        docLine(docHeader(docEditor('ics', 'splv'), 'SPLV-2026-11-001'), 0)
            ->set("form.{$field}", '')
            ->call('finalize')
            ->assertHasErrors(["form.{$field}" => $message]);

        expect(PropertyDocuments::count())->toBe(0);
        Event::assertNotDispatched(PropertyDocumentFinalized::class);
    })->with([
        'ICS No.' => ['document_number', 'The ICS No. field is required.'],
        'fund cluster' => ['fund_cluster', 'The fund cluster field is required.'],
        'received from (name)' => ['from_name', 'The received from (name) field is required.'],
        'received from (position)' => ['from_position', 'The received from (position) field is required.'],
        'received by (name)' => ['to_name', 'The received by (name) field is required.'],
        'received by (position)' => ['to_position', 'The received by (position) field is required.'],
    ]);

    it('rejects an ICS number already finalized on another ICS', function () {
        finalizedIcs('SPLV-2026-11-001');

        docLine(docHeader(docEditor('ics', 'splv'), 'SPLV-2026-11-001'), 0, ['property_number' => 'LV2026-0099', 'serial_number' => 'SN-0099'])
            ->call('finalize')
            ->assertHasErrors(['form.document_number' => 'The ICS No. has already been taken.']);
    });

    it('lets a PAR reuse a number an ICS already has', function () {
        PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => '2026-11-001']);

        docLine(docHeader(docEditor('par'), '2026-11-001'), 0, ['unit_cost' => '78000.00', 'property_number' => 'PPE-2026-0001'])
            ->call('finalize')
            ->assertHasNoErrors();

        expect(PropertyDocuments::query()->where('document_number', '2026-11-001')->count())->toBe(2);
    });

    it('blocks a document number off the pattern until an override reason is given', function () {
        docLine(docHeader(docEditor('ics', 'splv'), 'ICS-OLD-17'), 0)
            ->call('finalize')
            ->assertHasErrors(['form.document_number' => 'The ICS No. does not match the expected format (^(SPLV|SPHV)-\d{4}-\d{2}-\d{3}$). Check it, or give an override reason to save it anyway.']);

        expect(PropertyDocuments::count())->toBe(0);
    });

    it('finalizes a number off the pattern with an override reason and logs the override', function () {
        docLine(docHeader(docEditor('ics', 'splv'), 'ICS-OLD-17'), 0)
            ->set('form.pattern_override_reason', ' Number from the 2019 logbook ')
            ->call('finalize')
            ->assertHasNoErrors();

        $document = PropertyDocuments::sole();
        $override = AuditLogs::query()->where('action', AuditAction::DocumentPatternOverride)->sole();

        expect($document->status)->toBe(PropertyDocumentStatus::Active)
            ->and($override->auditable->is($document))->toBeTrue()
            ->and($override->new_values)->toBe(['document_number' => 'ICS-OLD-17'])
            ->and($override->reason)->toBe('Number from the 2019 logbook');
    });

    it('requires the ICS number prefix to match the slip class', function () {
        docLine(docHeader(docEditor('ics', 'splv'), 'SPHV-2026-11-001'), 0)
            ->call('finalize')
            ->assertHasErrors(['form.document_number' => 'This is an SPLV ICS, so its number must start with SPLV, not SPHV.']);
    });

    it('refuses a serial number on a line of more than one unit', function () {
        docLine(docHeader(docEditor('ics', 'splv'), 'SPLV-2026-11-001'), 0, ['quantity' => 2])
            ->call('finalize')
            ->assertHasErrors(['form.lines.0.serial_number' => 'A line with a serial number must have a quantity of 1.']);
    });

    it('refuses an incomplete line', function (string $field, mixed $value, string $message) {
        docLine(docHeader(docEditor('ics', 'splv'), 'SPLV-2026-11-001'), 0, [$field => $value])
            ->call('finalize')
            ->assertHasErrors(["form.lines.0.{$field}" => $message]);

        expect(PropertyDocuments::count())->toBe(0);
    })->with([
        'no property number' => ['property_number', '', 'The inventory item no. (line 1) field is required.'],
        'only the LV prefix' => ['property_number', 'LV', 'Complete the inventory item no. (line 1); it is only the prefix.'],
        'no date acquired' => ['date_acquired', '', 'The date acquired (line 1) field is required.'],
        'no useful life on an ICS' => ['estimated_useful_life_years', '', 'The estimated useful life (line 1) field is required.'],
    ]);

    it('refuses a property number repeated on the same document, ignoring case', function () {
        $page = docLine(docHeader(docEditor('ics', 'splv'), 'SPLV-2026-11-001'), 0)->call('addLine');

        docLine($page, 1, ['property_number' => 'lv2026-0001'])
            ->call('finalize')
            ->assertHasErrors(['form.lines.1.property_number' => 'The inventory item no. (line 2) lv2026-0001 is used on another line of this document.']);
    });

    it('accepts costs that belong on the document', function (string $type, ?string $class, string $number, string $cost, string $propertyNumber) {
        docLine(docHeader(docEditor($type, $class), $number), 0, ['unit_cost' => $cost, 'property_number' => $propertyNumber])
            ->call('finalize')
            ->assertHasNoErrors();

        expect(PropertyDocuments::sole()->status)->toBe(PropertyDocumentStatus::Active);
    })->with([
        '5,000.00 on an SPLV ICS' => ['ics', 'splv', 'SPLV-2026-11-001', '5000.00', 'LV2026-0001'],
        '5,000.01 on an SPHV ICS' => ['ics', 'sphv', 'SPHV-2026-11-001', '5000.01', 'HV2026-0001'],
        '49,999.99 on an SPHV ICS' => ['ics', 'sphv', 'SPHV-2026-11-001', '49999.99', 'HV2026-0001'],
        '50,000.00 on a PAR' => ['par', null, '2026-11-001', '50000.00', 'PPE-2026-0001'],
    ]);

    it('refuses costs that belong on another document and names where they go', function (string $type, ?string $class, string $number, string $cost, string $propertyNumber, string $message) {
        docLine(docHeader(docEditor($type, $class), $number), 0, ['unit_cost' => $cost, 'property_number' => $propertyNumber])
            ->call('finalize')
            ->assertHasErrors(['form.lines.0.unit_cost' => $message]);

        expect(PropertyDocuments::count())->toBe(0);
    })->with([
        '5,000.01 on an SPLV ICS' => ['ics', 'splv', 'SPLV-2026-11-001', '5000.01', 'LV2026-0001', '₱5,000.01 is SPHV, but this is an SPLV ICS. Put it on an SPHV ICS.'],
        '6,000.00 on an SPLV ICS' => ['ics', 'splv', 'SPLV-2026-11-001', '6000.00', 'LV2026-0001', '₱6,000.00 is SPHV, but this is an SPLV ICS. Put it on an SPHV ICS.'],
        '5,000.00 on an SPHV ICS' => ['ics', 'sphv', 'SPHV-2026-11-001', '5000.00', 'HV2026-0001', '₱5,000.00 is SPLV, but this is an SPHV ICS. Put it on an SPLV ICS.'],
        '50,000.00 on an SPHV ICS' => ['ics', 'sphv', 'SPHV-2026-11-001', '50000.00', 'HV2026-0001', '₱50,000.00 is at or above the PPE threshold (₱50,000.00). Record it on a PAR instead.'],
        '49,999.99 on a PAR' => ['par', null, '2026-11-001', '49999.99', 'PPE-2026-0001', '₱49,999.99 is below the PPE threshold (₱50,000.00), so it is SPHV. Record it on an ICS instead.'],
    ]);

    it('shows each line\'s cost class as it is typed', function () {
        $page = docEditor('ics', 'splv')->call('addLine')->call('addLine')->call('addLine');

        $page->set('form.lines.0.unit_cost', '5000.00')
            ->set('form.lines.1.unit_cost', '5000.01')
            ->set('form.lines.2.unit_cost', '50000')
            ->set('form.lines.3.unit_cost', 'abc')
            ->assertSet('lineClasses', [0 => 'splv', 1 => 'sphv', 2 => 'ppe', 3 => null]);
    });
});

describe('after finalizing', function () {
    it('refuses to add or remove lines', function () {
        $document = finalizedIcs();

        editDocument($document)
            ->assertSet('isFinalized', true)
            ->call('addLine')
            ->assertHasErrors(['form.lines' => 'Lines can\'t be added after the document is finalized.'])
            ->call('removeLine', 0)
            ->assertHasErrors(['form.lines' => 'Lines can\'t be removed after the document is finalized.'])
            ->assertCount('form.lines', 1);

        expect($document->lines()->count())->toBe(1);
    });

    it('refuses a correction without a reason', function () {
        $document = finalizedIcs();
        $logsBefore = AuditLogs::count();

        editDocument($document)
            ->set('form.to_name', 'Juan Receiver')
            ->call('save')
            ->assertHasErrors(['form.change_reason' => 'The reason for the change field is required.']);

        expect($document->fresh()->to_name)->toBe('Jane Requester')
            ->and(AuditLogs::count())->toBe($logsBefore);
    });

    it('saves a correction with a reason and logs exactly what changed', function () {
        $document = finalizedIcs();
        $logsBefore = AuditLogs::count();

        editDocument($document)
            ->set('form.to_name', 'Juan Receiver')
            ->set('form.change_reason', 'Wrong accountable person typed')
            ->call('save')
            ->assertHasNoErrors();

        $log = AuditLogs::query()->latest('id')->first();

        expect($document->fresh()->to_name)->toBe('Juan Receiver')
            ->and(AuditLogs::count())->toBe($logsBefore + 1)
            ->and($log->action)->toBe(AuditAction::Updated)
            ->and($log->auditable->is($document))->toBeTrue()
            ->and($log->changes())->toBe(['to_name' => ['Jane Requester', 'Juan Receiver']])
            ->and($log->reason)->toBe('Wrong accountable person typed');
    });

    it('refuses to delete a finalized document', function () {
        $document = finalizedIcs();

        editDocument($document)
            ->call('deleteDraft')
            ->assertHasErrors(['form.document_id' => 'Finalized documents cannot be deleted. Change their status instead.']);

        $this->assertModelExists($document);
    });
});

describe('status changes', function () {
    it('moves an active document to a closing status with a reason and logs it', function (PropertyDocumentStatus $to) {
        $document = finalizedIcs();

        editDocument($document)
            ->assertSet('statusOptions', [
                'returned' => PropertyDocumentStatus::Returned->label(),
                'transferred' => PropertyDocumentStatus::Transferred->label(),
                'renewed' => PropertyDocumentStatus::Renewed->label(),
                'disposed' => PropertyDocumentStatus::Disposed->label(),
            ])
            ->call('changeStatus', $to->value, 'Unit turned over to supply')
            ->assertHasNoErrors();

        $log = AuditLogs::query()->where('action', AuditAction::StatusChanged)->sole();

        expect($document->fresh()->status)->toBe($to)
            ->and($log->auditable->is($document))->toBeTrue()
            ->and($log->changes())->toBe(['status' => ['active', $to->value]])
            ->and($log->reason)->toBe('Unit turned over to supply');
    })->with([
        PropertyDocumentStatus::Returned,
        PropertyDocumentStatus::Transferred,
        PropertyDocumentStatus::Renewed,
        PropertyDocumentStatus::Disposed,
    ]);

    it('requires a reason of at least 5 characters', function (string $reason, string $message) {
        $document = finalizedIcs();

        editDocument($document)
            ->call('changeStatus', 'returned', $reason)
            ->assertHasErrors(['form.change_reason' => $message]);

        expect($document->fresh()->status)->toBe(PropertyDocumentStatus::Active);
    })->with([
        'no reason' => ['', 'The reason field is required.'],
        'too short' => ['oops', 'The reason field must be at least 5 characters.'],
    ]);

    it('refuses a status that is not a closing status', function (string $status, string $message) {
        $document = finalizedIcs();

        editDocument($document)
            ->call('changeStatus', $status, 'Trying to change it back')
            ->assertHasErrors(['form.status' => $message]);

        expect($document->fresh()->status)->toBe(PropertyDocumentStatus::Active)
            ->and(AuditLogs::query()->where('action', AuditAction::StatusChanged)->count())->toBe(0);
    })->with([
        'back to draft' => ['draft', 'A document can only be marked Returned, Transferred, Renewed or Disposed.'],
        'active again' => ['active', 'A document can only be marked Returned, Transferred, Renewed or Disposed.'],
        'an unknown status' => ['lost', 'Choose a valid status.'],
    ]);

    it('only changes the status of an active document', function (PropertyDocuments $document) {
        editDocument($document)
            ->call('changeStatus', 'disposed', 'Unit condemned')
            ->assertHasErrors(['form.status' => 'Only an active document can change status.']);

        expect(AuditLogs::query()->where('action', AuditAction::StatusChanged)->count())->toBe(0);
    })->with([
        'a draft' => fn () => PropertyDocuments::factory()->ics(PropertyClass::Splv)->create(),
        'a returned document' => fn () => PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['status' => PropertyDocumentStatus::Returned]),
    ]);

    it('offers no status changes on a draft', function () {
        editDocument(PropertyDocuments::factory()->ics(PropertyClass::Splv)->create())->assertSet('statusOptions', []);
    });
});

describe('deletes', function () {
    it('deletes a removed draft line on save and logs it', function () {
        $page = docLine(docEditor('ics', 'splv'), 0)->call('addLine');
        docLine($page, 1)->call('save')->assertHasNoErrors();
        $document = PropertyDocuments::sole();
        $removed = $document->lines()->orderBy('id')->get()->last();

        editDocument($document)->call('removeLine', 1)->call('save')->assertHasNoErrors();

        $this->assertModelMissing($removed);
        expect($document->lines()->count())->toBe(1)
            ->and(AuditLogs::query()->where('action', AuditAction::Deleted)->get()->map->only('auditable_type', 'auditable_id')->all())
            ->toBe([['auditable_type' => $removed->getMorphClass(), 'auditable_id' => (string) $removed->id]]);
    });

    it('keeps a line that carries an issued unit', function () {
        $document = PropertyDocuments::factory()->ics(PropertyClass::Splv)->create();
        PropertyDocumentLines::factory()->for($document, 'document')->forUnit(IssuedPropertyUnits::factory()->create())->create();

        editDocument($document)
            ->call('removeLine', 0)
            ->assertHasErrors(['form.lines.0' => 'This line is an issued unit and stays on the document.'])
            ->assertCount('form.lines', 1);
    });

    it('deletes a draft with an audit row for each line and the document', function () {
        $page = docLine(docEditor('ics', 'splv'), 0)->call('addLine');
        docLine($page, 1)->call('save')->assertHasNoErrors();
        $document = PropertyDocuments::sole();
        $lineIds = $document->lines()->pluck('id')->map(fn (int $id) => (string) $id)->sort()->values()->all();

        editDocument($document)->call('deleteDraft')->assertHasNoErrors()->assertDispatched('property-document-saved');

        $deleted = AuditLogs::query()->where('action', AuditAction::Deleted)->get();

        $this->assertModelMissing($document);
        expect(PropertyDocumentLines::count())->toBe(0)
            ->and($deleted->where('auditable_type', (new PropertyDocumentLines)->getMorphClass())->pluck('auditable_id')->sort()->values()->all())->toBe($lineIds)
            ->and($deleted->where('auditable_type', $document->getMorphClass())->pluck('auditable_id')->all())->toBe([(string) $document->id]);
    });
});
