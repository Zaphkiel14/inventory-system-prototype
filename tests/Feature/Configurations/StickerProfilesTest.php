<?php

use App\Enums\TagPrintKind;
use App\Livewire\Configurations\StickerProfileForm;
use App\Livewire\Configurations\StickerProfiles;
use App\Models\PropertyTagPrints;
use App\Models\StickerSheetProfiles;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * The create modal with a valid 3 × 8 Letter layout typed in.
 */
function newStickerProfile(): Testable
{
    return Livewire::test(StickerProfileForm::class)
        ->call('create')
        ->set('form.name', 'Letter 3 × 8')
        ->set('form.page_width_mm', '215.90')
        ->set('form.page_height_mm', '279.40')
        ->set('form.columns', 3)
        ->set('form.rows', 8)
        ->set('form.tag_width_mm', '66.70')
        ->set('form.tag_height_mm', '25.40')
        ->set('form.margin_top_mm', '12.70')
        ->set('form.margin_left_mm', '4.80')
        ->set('form.gap_x_mm', '3.10')
        ->set('form.gap_y_mm', '0')
        ->set('form.offset_x_mm', '-0.50')
        ->set('form.offset_y_mm', '1.25');
}

it('adds a profile without taking over the default', function () {
    $default = StickerSheetProfiles::default();

    newStickerProfile()->call('save')->assertHasNoErrors()->assertDispatched('sticker-profile-saved');

    $profile = StickerSheetProfiles::query()->where('name', 'Letter 3 × 8')->sole();
    expect($profile->only('columns', 'rows', 'tag_width_mm', 'offset_x_mm', 'offset_y_mm', 'is_default'))->toBe([
        'columns' => 3, 'rows' => 8, 'tag_width_mm' => '66.70', 'offset_x_mm' => '-0.50', 'offset_y_mm' => '1.25', 'is_default' => false,
    ])->and($default->fresh()->is_default)->toBeTrue();
});

it('makes the first profile ever saved the default', function () {
    StickerSheetProfiles::query()->delete();

    newStickerProfile()->call('save')->assertHasNoErrors();

    expect(StickerSheetProfiles::sole()->is_default)->toBeTrue();
});

it('updates an existing profile', function () {
    $profile = StickerSheetProfiles::factory()->create(['name' => 'Old name']);

    Livewire::test(StickerProfileForm::class)
        ->call('edit', $profile->id)
        ->assertSet('form.tag_width_mm', '95.00')
        ->set('form.name', 'A4 recalibrated')
        ->set('form.offset_x_mm', '0.75')
        ->call('save')
        ->assertHasNoErrors();

    expect($profile->fresh()->only('name', 'offset_x_mm'))->toBe(['name' => 'A4 recalibrated', 'offset_x_mm' => '0.75']);
});

it('rejects invalid profile fields', function (string $field, mixed $value, string $message) {
    newStickerProfile()->set("form.{$field}", $value)->call('save')->assertHasErrors(["form.{$field}" => $message]);

    expect(StickerSheetProfiles::query()->where('name', 'Letter 3 × 8')->exists())->toBeFalse();
})->with([
    'no name' => ['name', '', 'The name field is required.'],
    'no columns' => ['columns', 0, 'The columns field must be at least 1.'],
    'too many rows' => ['rows', 21, 'The rows field must not be greater than 20.'],
    'a negative tag width' => ['tag_width_mm', '-1', 'The tag width field must be at least 0.'],
    'three decimals' => ['page_width_mm', '215.905', 'The page width field must have 0-2 decimal places.'],
    'an offset past 20 mm' => ['offset_x_mm', '20.01', 'The horizontal offset field must not be greater than 20.'],
    'an offset past -20 mm' => ['offset_y_mm', '-20.01', 'The vertical offset field must be at least -20.'],
]);

it('rejects a layout that does not fit the page', function () {
    $page = newStickerProfile()
        ->set('form.columns', 4)
        ->assertSet('layoutFits', false)
        ->call('save')
        ->assertHasErrors('form.layout');

    // Compared directly: assertHasErrors() would read the colon in this message as rule parameters.
    expect($page->errors()->get('form.layout'))->toBe(['The tags don\'t fit on the page: the left margin plus 4 columns and their gaps must fit the page width, and the top margin plus 8 rows and their gaps must fit the page height. Make the tags, margins or gaps smaller, or use fewer columns or rows.'])
        ->and(StickerSheetProfiles::query()->where('name', 'Letter 3 × 8')->exists())->toBeFalse();
});

it('previews the typed grid slot by slot', function () {
    $page = newStickerProfile()->assertSet('layoutFits', true);

    $slots = $page->instance()->previewSlots;

    expect($slots)->toHaveCount(24)
        ->and([$slots[0]['x_mm'], $slots[0]['y_mm']])->toBe([4.3, 13.95])
        ->and([$slots[23]['row'], $slots[23]['column'], $slots[23]['x_mm'], $slots[23]['y_mm']])->toBe([8, 3, 143.9, 191.75]);

    $page->set('form.columns', 0)->assertSet('previewSlots', []);
});

it('makes another profile the default', function () {
    $previous = StickerSheetProfiles::default();
    $profile = StickerSheetProfiles::factory()->create();

    Livewire::test(StickerProfiles::class)->call('makeDefault', $profile->id)->assertDispatched('sticker-profile-saved');

    expect($profile->fresh()->is_default)->toBeTrue()
        ->and($previous->fresh()->is_default)->toBeFalse();
});

it('deletes an unused profile that is not the default', function () {
    $profile = StickerSheetProfiles::factory()->create();

    Livewire::test(StickerProfiles::class)->call('delete', $profile->id)->assertDispatched('sticker-profile-saved');

    $this->assertModelMissing($profile);
});

it('keeps a profile that was used for a print or is the default', function (Closure $profile) {
    $profile = $profile();

    Livewire::test(StickerProfiles::class)->call('delete', $profile->id)->assertNotDispatched('sticker-profile-saved');

    $this->assertModelExists($profile);
})->with([
    'used for a print' => [function () {
        $profile = StickerSheetProfiles::factory()->create();
        PropertyTagPrints::create(['kind' => TagPrintKind::Calibration, 'sticker_sheet_profile_id' => $profile->id, 'printed_at' => now()]);

        return $profile;
    }],
    'the default' => [fn () => StickerSheetProfiles::default()],
]);

it('lists each profile with its print count and slots per sheet', function () {
    $profile = StickerSheetProfiles::factory()->create(['columns' => 3, 'rows' => 8]);
    PropertyTagPrints::create(['kind' => TagPrintKind::Calibration, 'sticker_sheet_profile_id' => $profile->id, 'printed_at' => now()]);

    $listed = Livewire::test(StickerProfiles::class)->instance()->profiles->keyBy('id');

    expect([$listed[$profile->id]->prints_count, $listed[$profile->id]->slots_per_sheet])->toBe([1, 24]);
});
