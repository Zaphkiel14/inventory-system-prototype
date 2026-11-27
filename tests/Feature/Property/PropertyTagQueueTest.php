<?php

use App\Enums\PropertyClass;
use App\Enums\PropertyTagStatus;
use App\Enums\TagPrintKind;
use App\Livewire\PropertyManagement\PropertyTagQueue;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\PropertyTagPrints;
use App\Models\PropertyTags;
use App\Models\Requisitions;
use App\Models\StickerSheetProfiles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Sam Custodian']);
    $this->actingAs($this->user);
    $this->travelTo('2026-11-27 09:00:00');
});

/**
 * A tag on a new line of a finalized document.
 *
 * @param  array<string, mixed>  $document
 * @param  array<string, mixed>  $line
 */
function queuedTag(array $document = [], array $line = [], int $printed = 0): PropertyTags
{
    $doc = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create($document);
    $row = PropertyDocumentLines::factory()->for($doc, 'document')->create($line);
    $factory = PropertyTags::factory()->for($row, 'line');

    return ($printed > 0 ? $factory->printed($printed) : $factory)->create();
}

/**
 * The ids a queue currently lists, in order.
 *
 * @return list<int>
 */
function queuedIds(Testable $page): array
{
    return $page->instance()->tags->getCollection()->pluck('id')->all();
}

function tagQueue(): Testable
{
    return Livewire::test(PropertyTagQueue::class);
}

describe('the queue', function () {
    it('lists pending tags in print order: document date, then document, then line', function () {
        $later = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_date' => '2026-11-20']);
        $laterFirst = PropertyTags::factory()->for(PropertyDocumentLines::factory()->for($later, 'document'), 'line')->create();
        $laterSecond = PropertyTags::factory()->for(PropertyDocumentLines::factory()->for($later, 'document'), 'line')->create();
        $earlier = queuedTag(['document_date' => '2026-11-10']);
        queuedTag(['document_date' => '2026-11-01'], printed: 1);

        expect(queuedIds(tagQueue()))->toBe([$earlier->id, $laterFirst->id, $laterSecond->id]);
    });

    it('filters the queue', function (string $filter, mixed $value, array $expected) {
        $requisition = Requisitions::factory()->approved()->create(['office' => 'Emergency Unit (EU)']);
        $tags = [
            'fan' => queuedTag(
                ['document_number' => 'SPLV-2026-11-001', 'to_name' => 'Jane Requester', 'fund_cluster' => '01 - Regular Agency Fund', 'requisition_id' => $requisition->id, 'document_date' => '2026-11-10'],
                ['property_number' => 'LV2026-0001', 'item_name' => 'Electric Fan', 'serial_number' => 'SN-FAN-1'],
            ),
            'chair' => queuedTag(
                ['document_number' => 'SPLV-2026-11-002', 'to_name' => 'Pedro Penduko', 'fund_cluster' => '05 - Trust Fund', 'document_date' => '2026-11-20'],
                ['property_number' => 'LV2026-0002', 'item_name' => 'Office Chair', 'serial_number' => 'SN-CH-9'],
            ),
            'printed' => queuedTag(['document_date' => '2026-11-15'], ['property_number' => 'LV2026-0003', 'item_name' => 'Wall Clock'], printed: 1),
        ];
        $tags['fridge'] = PropertyTags::factory()->for(PropertyDocumentLines::factory()->for(
            PropertyDocuments::factory()->par()->finalized()->create(['document_date' => '2026-11-25', 'to_name' => 'Ana Reyes']),
            'document',
        )->state(['property_number' => 'PPE2026-0001', 'item_name' => 'Refrigerator', 'unit_cost' => '78000.00']), 'line')->create();

        $listed = tagQueue()->set($filter, $value)->instance()->tags->getCollection()
            ->map(fn (PropertyTags $tag) => array_search($tag->id, array_map(fn (PropertyTags $t) => $t->id, $tags), true))
            ->all();

        expect($listed)->toBe($expected);
    })->with([
        'property number' => ['search', 'LV2026-0002', ['chair']],
        'item name' => ['search', 'Electric', ['fan']],
        'serial number' => ['search', 'SN-CH', ['chair']],
        'accountable person' => ['search', 'Pedro', ['chair']],
        'document number' => ['search', 'SPLV-2026-11-001', ['fan']],
        'printed' => ['status', 'printed', ['printed']],
        'every status' => ['status', '', ['fan', 'printed', 'chair', 'fridge']],
        'PAR only' => ['documentType', 'par', ['fridge']],
        'fund cluster' => ['fundCluster', '05 - Trust', ['chair']],
        'office' => ['office', 'Emergency', ['fan']],
        'from a date' => ['from', '2026-11-20', ['chair', 'fridge']],
        'up to a date' => ['to', '2026-11-10', ['fan']],
    ]);

    it('renders the queue page', function () {
        queuedTag();

        $this->get(route('property-management.tags'))->assertOk();
    });
});

describe('selection', function () {
    it('keeps the selection across pages and clears it on request', function () {
        $tags = collect(range(1, 4))->map(fn () => queuedTag());

        $page = tagQueue()->set('perPage', 2)->call('selectPage');
        $page->call('nextPage')->call('selectPage');

        expect($page->get('selected'))->toBe($tags->pluck('id')->all());

        $page->call('clearSelection')->assertSet('selected', []);
    });

    it('selects at most 200 matching tags, in print order', function () {
        $document = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create();
        $lines = PropertyDocumentLines::factory()->count(PropertyTagQueue::MAX_SELECTION + 1)->for($document, 'document')->create();
        $lines->each(fn (PropertyDocumentLines $line) => PropertyTags::factory()->for($line, 'line')->create());
        $inOrder = PropertyTags::query()->orderBy('property_document_line_id')->pluck('id')->all();

        $page = tagQueue()->call('selectAllMatching');

        expect($page->get('selected'))->toBe(array_slice($inOrder, 0, PropertyTagQueue::MAX_SELECTION));
    });

    it('summarises the selection with the sheets it needs from the starting slot', function (int $startSlot, int $sheets) {
        $tags = collect(range(1, 11))->map(fn () => queuedTag())
            ->merge([queuedTag(printed: 1), queuedTag(printed: 2)]);

        tagQueue()
            ->set('status', '')
            ->set('selected', $tags->pluck('id')->all())
            ->set('startSlot', $startSlot)
            ->assertSet('selectedSummary', ['count' => 13, 'printed' => 2, 'sheets' => $sheets]);
    })->with([
        'from slot 1' => [1, 2],
        'from slot 8' => [8, 2],
        'from slot 9' => [9, 3],
    ]);

    it('summarises an empty selection as nothing to print', function () {
        tagQueue()->assertSet('selectedSummary', ['count' => 0, 'printed' => 0, 'sheets' => 0]);
    });
});

describe('preview', function () {
    it('opens a signed sheet link for the selection without recording a print', function () {
        $tags = [queuedTag(), queuedTag()];
        $ids = array_map(fn (PropertyTags $tag) => $tag->id, $tags);

        $page = tagQueue()->set('selected', $ids)->set('startSlot', 3)->call('preview')
            ->assertHasNoErrors()
            ->assertSet('awaitingConfirmation', $ids)
            ->assertDispatched('open-pdf', function (string $event, array $params) use ($ids) {
                $request = Request::create($params['url']);

                return URL::hasValidSignature($request)
                    && $request->query('tags') === implode(',', $ids)
                    && (int) $request->query('start') === 3
                    && (int) $request->query('profile') === StickerSheetProfiles::default()->id;
            });

        expect(PropertyTagPrints::count())->toBe(0)
            ->and(PropertyTags::query()->where('status', PropertyTagStatus::Pending)->count())->toBe(2);
    });

    it('refuses a preview that cannot be printed', function (Closure $arrange, string $field, string $message) {
        $tag = queuedTag();
        $printed = queuedTag(printed: 1);

        $page = $arrange(tagQueue(), $tag, $printed)->call('preview')
            ->assertHasErrors([$field => $message])
            ->assertNotDispatched('open-pdf')
            ->assertSet('awaitingConfirmation', []);

        expect(PropertyTagPrints::count())->toBe(0);
    })->with([
        'nothing selected' => [fn (Testable $page) => $page, 'selected', 'Select at least one tag to print.'],
        'over 200 selected' => [fn (Testable $page) => $page->set('selected', range(1, PropertyTagQueue::MAX_SELECTION + 1)), 'selected', 'Print at most 200 tags at a time.'],
        'starting slot 0' => [fn (Testable $page, $tag) => $page->set('selected', [$tag->id])->set('startSlot', 0), 'startSlot', 'The starting slot field must be at least 1.'],
        'starting slot past the sheet' => [fn (Testable $page, $tag) => $page->set('selected', [$tag->id])->set('startSlot', 11), 'startSlot', 'The starting slot field must not be greater than 10.'],
        'a reprint without a reason' => [fn (Testable $page, $tag, $printed) => $page->set('selected', [$tag->id, $printed->id]), 'reprintReason', '1 of the selected tags were printed before. Give a reason for reprinting them.'],
        'a reprint reason that is too short' => [fn (Testable $page, $tag, $printed) => $page->set('selected', [$printed->id])->set('reprintReason', 'torn'), 'reprintReason', 'The reprint reason field must be at least 5 characters.'],
    ]);
});

describe('a sticker profile that disappears', function () {
    it('refuses the preview when the chosen profile was deleted in another tab, without crashing the page', function () {
        $tag = queuedTag();
        $profile = StickerSheetProfiles::factory()->create();
        $page = tagQueue()->set('profileId', $profile->id)->set('selected', [$tag->id])->assertSet('profileId', $profile->id);

        StickerSheetProfiles::query()->whereKey($profile->id)->delete();

        $page->call('preview')
            ->assertHasErrors(['profileId' => 'Choose a sticker sheet profile.'])
            ->assertNotDispatched('open-pdf')
            ->assertSet('awaitingConfirmation', []);

        expect(PropertyTagPrints::count())->toBe(0);
    });

    it('falls back to the default profile when an unknown profile is picked', function () {
        tagQueue()->set('profileId', 999999)->assertSet('profileId', StickerSheetProfiles::default()->id);
    });
});

describe('confirming prints', function () {
    it('marks only the ticked tags printed and logs a first print or a reprint for each', function () {
        $first = queuedTag();
        $skipped = queuedTag();
        $again = queuedTag(printed: 1);
        $profile = StickerSheetProfiles::factory()->create();

        tagQueue()
            ->set('status', '')
            ->set('profileId', $profile->id)
            ->set('selected', [$first->id, $skipped->id, $again->id])
            ->set('reprintReason', ' Sticker peeled off ')
            ->call('preview')
            ->assertHasNoErrors()
            ->call('confirmPrinted', [$first->id, (string) $again->id, 999999])
            ->assertHasNoErrors()
            ->assertSet('selected', [])
            ->assertSet('awaitingConfirmation', [])
            ->assertSet('reprintReason', '');

        expect($first->fresh()->only('status', 'print_count', 'last_printed_by'))->toBe(['status' => PropertyTagStatus::Printed, 'print_count' => 1, 'last_printed_by' => $this->user->id])
            ->and($first->fresh()->last_printed_at->toDateTimeString())->toBe('2026-11-27 09:00:00')
            ->and($again->fresh()->only('status', 'print_count'))->toBe(['status' => PropertyTagStatus::Printed, 'print_count' => 2])
            ->and($skipped->fresh()->only('status', 'print_count', 'last_printed_at'))->toBe(['status' => PropertyTagStatus::Pending, 'print_count' => 0, 'last_printed_at' => null]);

        expect(PropertyTagPrints::query()->orderBy('property_tag_id')->get()->map->only('property_tag_id', 'kind', 'reason', 'sticker_sheet_profile_id', 'user_id')->all())->toBe([
            ['property_tag_id' => $first->id, 'kind' => TagPrintKind::First, 'reason' => null, 'sticker_sheet_profile_id' => $profile->id, 'user_id' => $this->user->id],
            ['property_tag_id' => $again->id, 'kind' => TagPrintKind::Reprint, 'reason' => 'Sticker peeled off', 'sticker_sheet_profile_id' => $profile->id, 'user_id' => $this->user->id],
        ]);
    });

    it('refuses a confirmation that ticks nothing from the last preview', function (Closure $ticked) {
        $previewed = queuedTag();
        $other = queuedTag();

        tagQueue()
            ->set('selected', [$previewed->id])
            ->call('preview')
            ->call('confirmPrinted', $ticked($other))
            ->assertHasErrors(['awaitingConfirmation' => 'Tick the tags that printed correctly.'])
            ->assertSet('awaitingConfirmation', [$previewed->id]);

        expect(PropertyTagPrints::count())->toBe(0)
            ->and($other->fresh()->status)->toBe(PropertyTagStatus::Pending);
    })->with([
        'nothing ticked' => [fn () => []],
        'only a tag outside the preview' => [fn (PropertyTags $other) => [$other->id]],
    ]);

    it('cannot be tricked into confirming tags the browser put in awaitingConfirmation', function () {
        $tag = queuedTag();

        try {
            tagQueue()->set('awaitingConfirmation', [$tag->id])->call('confirmPrinted', [$tag->id]);
        } catch (CannotUpdateLockedPropertyException) {
            // Locking the property is one acceptable way to refuse it.
        }

        expect($tag->fresh()->status)->toBe(PropertyTagStatus::Pending)
            ->and(PropertyTagPrints::count())->toBe(0);
    });

    it('refuses a confirmation before any preview', function () {
        $tag = queuedTag();

        tagQueue()->call('confirmPrinted', [$tag->id])->assertHasErrors(['awaitingConfirmation' => 'Tick the tags that printed correctly.']);

        expect($tag->fresh()->status)->toBe(PropertyTagStatus::Pending);
    });
});

describe('blank and calibration sheets', function () {
    it('logs a blank sheet and opens it', function (int $count, string $reason) {
        tagQueue()->set('blankCount', $count)->set('startSlot', 4)->call('printBlank')
            ->assertHasNoErrors()
            ->assertDispatched('open-pdf', fn (string $event, array $params) => URL::hasValidSignature(Request::create($params['url']))
                && (int) Request::create($params['url'])->query('count') === $count);

        expect(PropertyTagPrints::sole()->only('property_tag_id', 'kind', 'reason', 'sticker_sheet_profile_id'))->toBe([
            'property_tag_id' => null,
            'kind' => TagPrintKind::Blank,
            'reason' => $reason,
            'sticker_sheet_profile_id' => StickerSheetProfiles::default()->id,
        ]);
    })->with([
        'one sticker' => [1, '1 blank sticker'],
        'twelve stickers' => [12, '12 blank stickers'],
    ]);

    it('refuses a blank count outside 1 to 200', function (int $count, string $message) {
        tagQueue()->set('blankCount', $count)->call('printBlank')
            ->assertHasErrors(['blankCount' => $message])
            ->assertNotDispatched('open-pdf');

        expect(PropertyTagPrints::count())->toBe(0);
    })->with([
        'none' => [0, 'The number of blank stickers field must be at least 1.'],
        'over 200' => [201, 'The number of blank stickers field must not be greater than 200.'],
    ]);

    it('logs the calibration page and opens it', function () {
        tagQueue()->call('printCalibration')
            ->assertDispatched('open-pdf', fn (string $event, array $params) => URL::hasValidSignature(Request::create($params['url'])));

        expect(PropertyTagPrints::sole()->only('property_tag_id', 'kind', 'reason'))->toBe([
            'property_tag_id' => null,
            'kind' => TagPrintKind::Calibration,
            'reason' => null,
        ]);
    });
});
