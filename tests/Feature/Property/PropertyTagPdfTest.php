<?php

use App\Http\Controllers\Property\PropertyTagPdfController;
use App\Models\PropertyTagPrints;
use App\Models\PropertyTags;
use App\Models\StickerSheetProfiles;
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->travelTo('2026-11-27 09:00:00');
});

it('streams each sticker sheet as an inline PDF without recording a print', function (Closure $url, string $filename) {
    $this->actingAs(User::factory()->create());

    $this->get($url())
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', "inline; filename={$filename}");

    expect(PropertyTagPrints::count())->toBe(0);
})->with([
    'a sheet of tags' => [
        fn () => PropertyTagPdfController::sheetUrl(StickerSheetProfiles::default(), PropertyTags::factory()->count(3)->create()->modelKeys(), 9),
        'property-tags-20261127-090000.pdf',
    ],
    'blank stickers' => [fn () => PropertyTagPdfController::blankUrl(StickerSheetProfiles::default(), 12, 4), 'blank-stickers-20261127-090000.pdf'],
    'the calibration page' => [fn () => PropertyTagPdfController::calibrationUrl(StickerSheetProfiles::default()), 'tag-calibration-20261127-090000.pdf'],
]);

it('refuses a link without a valid signature', function (Closure $url) {
    $this->actingAs(User::factory()->create());

    $this->get($url())->assertForbidden();
})->with([
    'unsigned' => [fn () => route('property-management.tags.calibration', ['profile' => StickerSheetProfiles::default()->id])],
    'a tag id changed' => [fn () => str_replace('tags='.PropertyTags::factory()->create()->id, 'tags=999', PropertyTagPdfController::sheetUrl(StickerSheetProfiles::default(), [PropertyTags::query()->latest('id')->value('id')], 1))],
    'the blank count changed' => [fn () => str_replace('count=12', 'count=13', PropertyTagPdfController::blankUrl(StickerSheetProfiles::default(), 12, 1))],
]);

it('refuses a link once it has expired', function () {
    $this->actingAs(User::factory()->create());
    $url = PropertyTagPdfController::calibrationUrl(StickerSheetProfiles::default());

    $this->travel(PropertyTagPdfController::LINK_MINUTES)->minutes();
    $this->get($url)->assertOk();

    $this->travel(1)->seconds();
    $this->get($url)->assertForbidden();
});

it('rejects a signed link whose parameters are unusable', function (string $route, Closure $parameters, int $status) {
    $this->actingAs(User::factory()->create());
    $url = URL::temporarySignedRoute($route, now()->addMinutes(PropertyTagPdfController::LINK_MINUTES), $parameters());

    $this->get($url)->assertStatus($status);
})->with([
    'a missing profile' => ['property-management.tags.calibration', fn () => ['profile' => 999], 404],
    'no tags' => ['property-management.tags.sheet', fn () => ['tags' => '', 'profile' => StickerSheetProfiles::default()->id, 'start' => 1], 404],
    'a starting slot past the sheet' => ['property-management.tags.sheet', fn () => ['tags' => PropertyTags::factory()->create()->id, 'profile' => StickerSheetProfiles::default()->id, 'start' => 11], 422],
    'a starting slot of 0' => ['property-management.tags.blank', fn () => ['count' => 1, 'profile' => StickerSheetProfiles::default()->id, 'start' => 0], 422],
    'too many blank stickers' => ['property-management.tags.blank', fn () => ['count' => PropertyTagPdfController::MAX_BLANK + 1, 'profile' => StickerSheetProfiles::default()->id, 'start' => 1], 422],
]);

it('redirects a guest with a valid link to the login page', function () {
    $this->get(PropertyTagPdfController::calibrationUrl(StickerSheetProfiles::default()))->assertRedirect(route('login'));
});
