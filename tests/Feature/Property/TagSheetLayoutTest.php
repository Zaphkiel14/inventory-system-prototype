<?php

use App\Models\StickerSheetProfiles;
use App\Services\Property\TagSheetLayout;

/**
 * Each placed slot as [index, row, column, x mm, y mm].
 *
 * @param  list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}>  $slots
 * @return list<array{0: int, 1: int, 2: int, 3: float, 4: float}>
 */
function slotPositions(array $slots): array
{
    return array_map(fn (array $slot) => [$slot['index'], $slot['row'], $slot['column'], $slot['x_mm'], $slot['y_mm']], $slots);
}

/**
 * @param  list<list<array{index: int}>>  $sheets
 * @return list<list<int>>
 */
function slotIndexes(array $sheets): array
{
    return array_map(fn (array $sheet) => array_column($sheet, 'index'), $sheets);
}

describe('the default A4 2 × 5 profile', function () {
    it('places a full sheet across then down at exact millimetres', function () {
        $sheets = TagSheetLayout::place(StickerSheetProfiles::default(), 10);

        expect($sheets)->toHaveCount(1)
            ->and(slotPositions($sheets[0]))->toBe([
                [1, 1, 1, 7.5, 9.5],
                [2, 1, 2, 107.5, 9.5],
                [3, 2, 1, 7.5, 65.5],
                [4, 2, 2, 107.5, 65.5],
                [5, 3, 1, 7.5, 121.5],
                [6, 3, 2, 107.5, 121.5],
                [7, 4, 1, 7.5, 177.5],
                [8, 4, 2, 107.5, 177.5],
                [9, 5, 1, 7.5, 233.5],
                [10, 5, 2, 107.5, 233.5],
            ])
            ->and([$sheets[0][0]['width_mm'], $sheets[0][0]['height_mm']])->toBe([95.0, 54.0]);
    });

    it('starts the first sheet at the chosen slot and later sheets at slot 1', function () {
        $sheets = TagSheetLayout::place(StickerSheetProfiles::default(), 9, startSlot: 4);

        expect(slotIndexes($sheets))->toBe([[4, 5, 6, 7, 8, 9, 10], [1, 2]])
            ->and(slotPositions([$sheets[0][0]]))->toBe([[4, 2, 2, 107.5, 65.5]])
            ->and(slotPositions([$sheets[1][0]]))->toBe([[1, 1, 1, 7.5, 9.5]]);
    });

    it('spreads tags over as many sheets as they need', function () {
        $sheets = TagSheetLayout::place(StickerSheetProfiles::default(), 23);

        expect(array_map('count', $sheets))->toBe([10, 10, 3])
            ->and(array_column($sheets[2], 'index'))->toBe([1, 2, 3]);
    });

    it('fills exactly the rest of a sheet from the last slot', function () {
        expect(slotIndexes(TagSheetLayout::place(StickerSheetProfiles::default(), 1, startSlot: 10)))->toBe([[10]])
            ->and(slotIndexes(TagSheetLayout::place(StickerSheetProfiles::default(), 2, startSlot: 10)))->toBe([[10], [1]]);
    });

    it('places nothing for no tags', function () {
        expect(TagSheetLayout::place(StickerSheetProfiles::default(), 0))->toBe([]);
    });

    it('rejects a starting slot off the sheet or a negative count', function (int $count, int $startSlot, string $message) {
        expect(fn () => TagSheetLayout::place(StickerSheetProfiles::default(), $count, $startSlot))
            ->toThrow(InvalidArgumentException::class, $message);
    })->with([
        'slot 0' => [1, 0, 'The starting slot must be between 1 and 10.'],
        'slot 11' => [1, 11, 'The starting slot must be between 1 and 10.'],
        'a negative count' => [-1, 1, 'The number of tags cannot be negative.'],
    ]);

    it('lays out every slot of one sheet for the calibration page', function () {
        $profile = StickerSheetProfiles::default();

        expect(TagSheetLayout::slotsPerSheet($profile))->toBe(10)
            ->and(TagSheetLayout::calibration($profile))->toBe(TagSheetLayout::place($profile, 10)[0]);
    });
});

it('shifts every slot by the calibration offsets', function (string $offsetX, string $offsetY, array $first, array $last) {
    $profile = StickerSheetProfiles::factory()->create(['offset_x_mm' => $offsetX, 'offset_y_mm' => $offsetY]);

    $slots = TagSheetLayout::place($profile, 10)[0];

    expect([$slots[0]['x_mm'], $slots[0]['y_mm']])->toBe($first)
        ->and([$slots[9]['x_mm'], $slots[9]['y_mm']])->toBe($last)
        ->and([$slots[0]['width_mm'], $slots[0]['height_mm']])->toBe([95.0, 54.0]);
})->with([
    'left and down' => ['-1.25', '0.75', [6.25, 10.25], [106.25, 234.25]],
    'right and up' => ['2.50', '-0.50', [10.0, 9.0], [110.0, 233.0]],
]);

it('counts the slots of a custom grid', function () {
    expect(TagSheetLayout::slotsPerSheet(StickerSheetProfiles::factory()->create(['columns' => 3, 'rows' => 8])))->toBe(24);
});

it('checks whether the grid fits on the page', function (array $changes, bool $fits) {
    expect(TagSheetLayout::fits(StickerSheetProfiles::factory()->create($changes)))->toBe($fits);
})->with([
    'the default layout' => [[], true],
    'exactly as wide as the page' => [['page_width_mm' => '202.50'], true],
    'a hundredth too narrow' => [['page_width_mm' => '202.49'], false],
    'exactly as tall as the page' => [['page_height_mm' => '287.50'], true],
    'a hundredth too short' => [['page_height_mm' => '287.49'], false],
    'three columns on A4' => [['columns' => 3], false],
    'six rows on A4' => [['rows' => 6], false],
    'no columns' => [['columns' => 0], false],
    'no tag height' => [['tag_height_mm' => '0.00'], false],
    'a negative gap' => [['gap_x_mm' => '-1.00'], false],
    'a negative margin' => [['margin_top_mm' => '-0.50'], false],
    'large calibration offsets are not counted' => [['offset_x_mm' => '50.00', 'offset_y_mm' => '-50.00'], true],
]);

describe('the default profile', function () {
    it('exists once after migrating, as the standard A4 2 × 5 layout', function () {
        $default = StickerSheetProfiles::default();

        expect(StickerSheetProfiles::query()->where('is_default', true)->pluck('id')->all())->toBe([$default->id])
            ->and($default->only(array_keys(StickerSheetProfiles::DEFAULT_PROFILE)))->toEqual(StickerSheetProfiles::DEFAULT_PROFILE);
    });

    it('moves to a newly created default profile', function () {
        $previous = StickerSheetProfiles::default();

        $new = StickerSheetProfiles::factory()->asDefault()->create();

        expect($previous->fresh()->is_default)->toBeFalse()
            ->and(StickerSheetProfiles::query()->where('is_default', true)->pluck('id')->all())->toBe([$new->id])
            ->and(StickerSheetProfiles::default()->is($new))->toBeTrue();
    });

    it('moves to an existing profile marked as default', function () {
        $previous = StickerSheetProfiles::default();
        $other = StickerSheetProfiles::factory()->create();

        $other->update(['is_default' => true]);

        expect($previous->fresh()->is_default)->toBeFalse()
            ->and(StickerSheetProfiles::default()->is($other))->toBeTrue();
    });

    it('recreates the standard default when no profile is left', function () {
        StickerSheetProfiles::query()->delete();

        $default = StickerSheetProfiles::default();

        expect($default->is_default)->toBeTrue()
            ->and($default->name)->toBe('A4 · 2 × 5')
            ->and(StickerSheetProfiles::count())->toBe(1);
    });
});
