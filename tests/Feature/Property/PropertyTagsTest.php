<?php

use App\Enums\IcsTagging;
use App\Enums\PropertyClass;
use App\Enums\PropertyTagStatus;
use App\Enums\TagPrintKind;
use App\Events\PropertyDocumentFinalized;
use App\Livewire\PropertyManagement\PropertyDocumentEditor;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\PropertyTagPrints;
use App\Models\PropertyTags;
use App\Models\StickerSheetProfiles;
use App\Models\User;
use App\Services\Property\PropertyTagContent;
use App\Support\PropertySettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Sam Custodian']));
    $this->travelTo('2026-11-27 09:00:00');
});

/**
 * Create a document with $type ('ics' or 'par') and $lineCount valid lines
 * through the editor, and finalize it.
 */
function finalizeThroughEditor(string $type, ?string $icsClass, string $number, string $unitCost, int $lineCount = 2): PropertyDocuments
{
    $prefix = match ($icsClass) {
        'splv' => 'LV',
        'sphv' => 'HV',
        default => 'PPE',
    };

    $page = Livewire::test(PropertyDocumentEditor::class)
        ->call('create', $type, $icsClass)
        ->set('form.document_number', $number)
        ->set('form.fund_cluster', '01 - Regular Agency Fund')
        ->set('form.from_name', 'Sam Custodian')
        ->set('form.from_position', 'Supply Officer III')
        ->set('form.to_name', 'Jane Requester')
        ->set('form.to_position', 'Nurse II');

    foreach (range(0, $lineCount - 1) as $i) {
        if ($i > 0) {
            $page->call('addLine');
        }

        $page->set("form.lines.{$i}.item_name", 'Electric Fan')
            ->set("form.lines.{$i}.item_type", 'Stand fan')
            ->set("form.lines.{$i}.unit", 'unit')
            ->set("form.lines.{$i}.unit_cost", $unitCost)
            ->set("form.lines.{$i}.brand", 'Asahi')
            ->set("form.lines.{$i}.model", 'EF-1600')
            ->set("form.lines.{$i}.serial_number", "SN-{$number}-{$i}")
            ->set("form.lines.{$i}.property_number", "{$prefix}2026-{$i}")
            ->set("form.lines.{$i}.estimated_useful_life_years", 5)
            ->set("form.lines.{$i}.date_acquired", '2026-10-01');
    }

    $page->call('finalize')->assertHasNoErrors();

    return PropertyDocuments::query()->where('document_number', $number)->sole();
}

/**
 * A finalized document with two lines, made with factories (no event fired).
 */
function finalizedWithLines(?PropertyClass $icsClass): PropertyDocuments
{
    $factory = $icsClass === null
        ? PropertyDocuments::factory()->par()
        : PropertyDocuments::factory()->ics($icsClass);

    $document = $factory->finalized()->create();
    PropertyDocumentLines::factory()->count(2)->for($document, 'document')->create();

    return $document;
}

it('queues a Pending tag for every line when a PAR is finalized', function () {
    $par = finalizeThroughEditor('par', null, '2026-11-003', '78000.00');

    $tags = PropertyTags::query()->with('line')->get();

    expect($tags->pluck('line.property_document_id')->unique()->all())->toBe([$par->id])
        ->and($tags->pluck('property_document_line_id')->sort()->values()->all())->toBe($par->lines()->orderBy('id')->pluck('id')->all())
        ->and($tags->map->only('status', 'print_count', 'last_printed_at', 'last_printed_by')->unique()->values()->all())->toBe([[
            'status' => PropertyTagStatus::Pending,
            'print_count' => 0,
            'last_printed_at' => null,
            'last_printed_by' => null,
        ]]);
});

it('tags ICS lines as the ICS tagging setting allows', function (IcsTagging $setting, PropertyClass $class, int $expected) {
    PropertySettings::setIcsTagging($setting);
    $ics = finalizedWithLines($class);

    PropertyDocumentFinalized::dispatch($ics);

    expect(PropertyTags::count())->toBe($expected);
})->with([
    'all, SPLV' => [IcsTagging::All, PropertyClass::Splv, 2],
    'all, SPHV' => [IcsTagging::All, PropertyClass::Sphv, 2],
    'SPHV only, SPLV' => [IcsTagging::SphvOnly, PropertyClass::Splv, 0],
    'SPHV only, SPHV' => [IcsTagging::SphvOnly, PropertyClass::Sphv, 2],
    'none, SPLV' => [IcsTagging::None, PropertyClass::Splv, 0],
    'none, SPHV' => [IcsTagging::None, PropertyClass::Sphv, 0],
]);

it('always tags a PAR, whatever the ICS tagging setting', function () {
    PropertySettings::setIcsTagging(IcsTagging::None);

    PropertyDocumentFinalized::dispatch(finalizedWithLines(null));

    expect(PropertyTags::count())->toBe(2);
});

it('applies the ICS tagging setting when an ICS is finalized through the editor', function () {
    PropertySettings::setIcsTagging(IcsTagging::SphvOnly);

    finalizeThroughEditor('ics', 'splv', 'SPLV-2026-11-001', '1196.11');
    $sphv = finalizeThroughEditor('ics', 'sphv', 'SPHV-2026-11-001', '6000.00');

    expect(PropertyTags::query()->with('line')->get()->pluck('line.property_document_id')->unique()->all())->toBe([$sphv->id])
        ->and(PropertyTags::count())->toBe(2);
});

it('never gives a line a second tag when the event fires again', function () {
    $par = finalizedWithLines(null);
    PropertyDocumentFinalized::dispatch($par);
    $printed = PropertyTags::query()->orderBy('id')->first();
    $printed->update(['status' => PropertyTagStatus::Printed, 'print_count' => 1]);

    PropertyDocumentFinalized::dispatch($par);

    expect(PropertyTags::count())->toBe(2)
        ->and($printed->fresh()->only('status', 'print_count'))->toBe(['status' => PropertyTagStatus::Printed, 'print_count' => 1]);
});

it('reads tag content from the line and its document', function () {
    $par = finalizeThroughEditor('par', null, '2026-11-003', '78000.5', lineCount: 1);

    $content = PropertyTagContent::for(PropertyTags::sole());

    expect(collect($content)->except('date_acquired')->all())->toBe([
        'property_number' => 'PPE2026-0',
        'item' => 'Electric Fan, Stand fan',
        'model' => 'Asahi / EF-1600',
        'serial_number' => 'SN-2026-11-003-0',
        'cost' => '78000.50',
        'accountable_person' => 'Jane Requester',
        'document_label' => 'PAR 2026-11-003',
    ])->and($content['date_acquired']->toDateString())->toBe('2026-10-01')
        ->and($par->lines()->sole()->id)->toBe(PropertyTags::sole()->property_document_line_id);
});

it('leaves out empty parts of the item and model', function (array $line, string $item, string $model) {
    $tag = PropertyTags::factory()->for(PropertyDocumentLines::factory()->state(['item_name' => 'Electric Fan', ...$line]), 'line')->create();

    expect(PropertyTagContent::for($tag))
        ->item->toBe($item)
        ->model->toBe($model);
})->with([
    'brand only' => [['item_type' => null, 'brand' => 'Asahi', 'model' => null], 'Electric Fan', 'Asahi'],
    'model only' => [['item_type' => '', 'brand' => '', 'model' => 'EF-1600'], 'Electric Fan', 'EF-1600'],
    'neither' => [['item_type' => null, 'brand' => null, 'model' => null], 'Electric Fan', ''],
]);

it('shows a correction made after finalizing, because content is never copied onto the tag', function () {
    $par = finalizeThroughEditor('par', null, '2026-11-003', '78000.00', lineCount: 1);
    $tag = PropertyTags::sole();

    Livewire::test(PropertyDocumentEditor::class)
        ->call('edit', $par->id)
        ->set('form.to_name', 'Juan Receiver')
        ->set('form.lines.0.serial_number', 'SN-CORRECTED')
        ->set('form.change_reason', 'Serial misread from the box')
        ->call('save')
        ->assertHasNoErrors();

    expect(PropertyTagContent::for($tag->fresh()))
        ->serial_number->toBe('SN-CORRECTED')
        ->accountable_person->toBe('Juan Receiver');
});

it('keeps the print log permanent', function () {
    $print = PropertyTagPrints::create([
        'property_tag_id' => PropertyTags::factory()->create()->id,
        'user_id' => auth()->id(),
        'kind' => TagPrintKind::Reprint,
        'reason' => 'Sticker peeled off',
        'sticker_sheet_profile_id' => StickerSheetProfiles::default()->id,
        'printed_at' => now(),
    ]);

    expect(fn () => $print->update(['reason' => 'changed']))->toThrow(LogicException::class, 'Tag print records are permanent and cannot be modified.')
        ->and(fn () => $print->delete())->toThrow(LogicException::class, 'Tag print records are permanent and cannot be deleted.');

    expect($print->fresh()->reason)->toBe('Sticker peeled off');
});
