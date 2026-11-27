<?php

use App\Services\Property\TagTextFitter;

/*
 * At the 90 × 55 mm reference size (scale 1) the value column is 58.16 mm and
 * a row is 37 / 7 mm, which caps two-line text at about 5.35 pt.
 */

it('scales from the 90 × 55 mm reference tag by the tighter side', function () {
    expect(TagTextFitter::scaleFor(90, 55))->toBe(1.0)
        ->and(TagTextFitter::scaleFor(95, 54))->toBe(54 / 55)
        ->and(round(TagTextFitter::valueWidthFor(90, 1.0), 2))->toBe(58.16)
        ->and(TagTextFitter::rowHeightFor(55, 1.0))->toBe(37 / 7);
});

it('keeps short text on one line at the largest size', function () {
    expect(TagTextFitter::fitForTag('Electric Fan', 90, 55))
        ->toBe(['size_pt' => 7.5, 'lines' => ['Electric Fan']]);
});

it('returns no lines for empty text', function () {
    expect(TagTextFitter::fitForTag('   ', 90, 55))->toBe(['size_pt' => 7.5, 'lines' => []]);
});

it('wraps long text with spaces onto two lines at spaces', function () {
    $text = 'Refrigerator, Two-door Inverter, Frost-free, 9.1 cu ft, with ice maker and water dispenser';

    $fit = TagTextFitter::fitForTag($text, 90, 55);

    expect($fit['lines'])->toHaveCount(2)
        ->and(implode(' ', $fit['lines']))->toBe($text)
        ->and($fit['size_pt'])->toBeLessThanOrEqual(5.35)
        ->and($fit['size_pt'])->toBeGreaterThanOrEqual(3.8);
});

it('hard-cuts an unbroken 52-character serial across two lines', function () {
    $serial = str_repeat('AB12CD34EF', 5).'GH';

    $fit = TagTextFitter::fit($serial, 30.0, 1.0, 37 / 7);

    expect(mb_strlen($serial))->toBe(52)
        ->and($fit['lines'])->toHaveCount(2)
        ->and(implode('', $fit['lines']))->toBe($serial)
        ->and($fit['lines'][1])->not->toEndWith('…');
});

it('cuts overflowing text with an ellipsis at the minimum size', function () {
    $text = trim(str_repeat('Laboratory centrifuge accessory kit ', 8));

    $fit = TagTextFitter::fit($text, 30.0, 1.0, 37 / 7);

    expect($fit['size_pt'])->toBe(3.8)
        ->and($fit['lines'])->toHaveCount(2)
        ->and($fit['lines'][1])->toEndWith('…')
        ->and(mb_strlen($fit['lines'][1]))->toBeLessThanOrEqual(36);
});

it('never goes below the 3.8 pt minimum, scaled with the tag', function (string $text) {
    $scale = TagTextFitter::scaleFor(60, 36);
    $fit = TagTextFitter::fitForTag($text, 60, 36);

    expect($fit['size_pt'])->toBeGreaterThanOrEqual(TagTextFitter::floorPt(3.8 * $scale));
})->with([
    'short' => ['Fan'],
    'long words' => [trim(str_repeat('Stainless steel instrument trolley ', 10))],
    'unbroken' => [str_repeat('X', 400)],
]);
