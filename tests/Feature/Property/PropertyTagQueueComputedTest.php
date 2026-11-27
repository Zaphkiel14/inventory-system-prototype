<?php

use App\Livewire\PropertyManagement\PropertyDocumentEditor;
use App\Livewire\PropertyManagement\PropertyTagQueue;
use App\Livewire\Requisitions\Approvals;
use App\Livewire\Requisitions\Issuances;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\PropertyTags;
use App\Models\StickerSheetProfiles;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('gives the start-slot grid of the chosen profile, falling back to the default', function () {
    $letter = StickerSheetProfiles::factory()->create(['columns' => 3, 'rows' => 10, 'tag_width_mm' => 60, 'tag_height_mm' => 25]);

    $queue = Livewire::test(PropertyTagQueue::class);
    expect($queue->get('profileGrid'))->toBe(['columns' => 2, 'rows' => 5]);

    expect($queue->set('profileId', $letter->id)->get('profileGrid'))->toBe(['columns' => 3, 'rows' => 10])
        ->and($queue->set('profileId', 999999)->get('profileGrid'))->toBe(['columns' => 2, 'rows' => 5]);
});

it('lists the tags awaiting confirmation in print order with their content', function () {
    $later = PropertyDocuments::factory()->par()->finalized()->create(['document_date' => '2026-10-01']);
    $earlier = PropertyDocuments::factory()->ics()->finalized()->create(['document_date' => '2026-09-01']);
    $parTag = PropertyTags::factory()->for(PropertyDocumentLines::factory()->for($later, 'document')->state(['property_number' => 'PN-2']), 'line')->create();
    $icsTag = PropertyTags::factory()->for(PropertyDocumentLines::factory()->for($earlier, 'document')->state(['property_number' => 'PN-1']), 'line')->create();

    $queue = Livewire::test(PropertyTagQueue::class);
    expect($queue->get('awaitingTags'))->toBeEmpty();

    $queue->set('selected', [$parTag->id, $icsTag->id])->call('preview')->assertHasNoErrors();

    $awaiting = $queue->get('awaitingTags');
    expect($awaiting->pluck('id')->all())->toBe([$icsTag->id, $parTag->id])
        ->and($awaiting->first()->content['property_number'])->toBe('PN-1')
        ->and($awaiting->first()->relationLoaded('line'))->toBeTrue();

    $queue->call('cancelConfirmation');
    expect($queue->get('awaitingTags'))->toBeEmpty();
});

it('falls back to the default profile at render when the chosen one disappears', function () {
    $spare = StickerSheetProfiles::factory()->create(['columns' => 3, 'rows' => 10, 'tag_width_mm' => 60, 'tag_height_mm' => 25]);
    $queue = Livewire::test(PropertyTagQueue::class)->set('profileId', $spare->id);

    $spare->delete();

    expect($queue->call('$refresh')->get('slotsPerSheet'))->toBe(10)
        ->and($queue->get('selectedSummary'))->toBe(['count' => 0, 'printed' => 0, 'sheets' => 0]);

    // Deleted after it was chosen: no update hook fires, so actions refuse.
    $queue->set('selected', [PropertyTags::factory()->create()->id])
        ->call('preview')
        ->assertHasErrors(['profileId' => 'Choose a sticker sheet profile.'])
        ->assertNotDispatched('open-pdf');

    // An unknown id from the browser falls back to the default.
    $queue->set('profileId', 999999)->assertSet('profileId', StickerSheetProfiles::default()->id);
});

it('locks the ids only the server sets', function (string $component, string $property, mixed $value) {
    expect(fn () => Livewire::test($component)->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'queue awaitingConfirmation' => [PropertyTagQueue::class, 'awaitingConfirmation', [1]],
    'document id' => [PropertyDocumentEditor::class, 'form.document_id', 1],
    'document type' => [PropertyDocumentEditor::class, 'form.type', 'par'],
    'ICS class' => [PropertyDocumentEditor::class, 'form.ics_class', 'sphv'],
    'issuance requisition' => [Issuances::class, 'form.requisition_id', 1],
    'approval requisition' => [Approvals::class, 'form.requisition_id', 1],
]);
