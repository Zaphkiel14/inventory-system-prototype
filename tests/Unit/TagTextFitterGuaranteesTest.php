<?php

use App\Services\Property\TagTextFitter;

/*
 * The guarantees the property tag relies on so a value never spills into the
 * next row: every bound is derived from TagTextFitter's own constants and
 * size helpers, never from hand-computed numbers.
 */

/**
 * How many characters physically fit across the value column at $sizePt,
 * using the fitter's character-width estimate (no lower clamp).
 */
function fitterCapacity(float $valueWidthMm, float $sizePt): int
{
    return (int) floor($valueWidthMm / ($sizePt * TagTextFitter::CHAR_WIDTH_EM * TagTextFitter::PT_TO_MM));
}

/**
 * The value column width, row height and scale of a tag, as the fitter derives them.
 *
 * @return array{width: float, row: float, scale: float}
 */
function fitterGeometry(float $widthMm, float $heightMm): array
{
    $scale = TagTextFitter::scaleFor($widthMm, $heightMm);

    return [
        'width' => TagTextFitter::valueWidthFor($widthMm, $scale),
        'row' => TagTextFitter::rowHeightFor($heightMm, $scale),
        'scale' => $scale,
    ];
}

/**
 * The largest size at which two lines still fit the row height.
 */
function fitterTwoLineMaxPt(float $rowHeightMm, float $scale): float
{
    return min(TagTextFitter::MAX_PT * $scale, $rowHeightMm / (2 * TagTextFitter::RENDERED_LINE_EM * TagTextFitter::PT_TO_MM));
}

it('never returns a value that spills out of its row', function (string $text, array $tag) {
    ['width' => $width, 'row' => $row, 'scale' => $scale] = fitterGeometry(...$tag);

    $fit = TagTextFitter::fitForTag($text, ...$tag);
    $capacity = fitterCapacity($width, $fit['size_pt']);

    expect(count($fit['lines']))->toBeLessThanOrEqual(2)
        ->and($fit['size_pt'])->toBeGreaterThanOrEqual(floor(TagTextFitter::MIN_PT * $scale * 100) / 100)
        ->and($fit['size_pt'])->toBeLessThanOrEqual(round(TagTextFitter::MAX_PT * $scale, 2))
        ->and(count($fit['lines']) * $fit['size_pt'] * TagTextFitter::RENDERED_LINE_EM * TagTextFitter::PT_TO_MM)->toBeLessThanOrEqual($row);

    foreach ($fit['lines'] as $line) {
        expect(mb_strlen($line))->toBeLessThanOrEqual($capacity, "\"{$line}\" is wider than {$capacity} characters at {$fit['size_pt']} pt");
    }
})->with([
    'short' => 'Fan',
    'item with type' => 'Electric Fan, Stand fan',
    'just over one line' => 'Refrigerator Two-door Inverter Frost-free',
    'long with spaces' => 'Refrigerator, Two-door Inverter, Frost-free, 9.1 cu ft, with ice maker and water dispenser',
    'unbroken serial' => str_repeat('AB12CD34EF', 5).'GH',
    'multibyte name' => 'Ma. Niña Peñaflorida-Ibáñez',
    'multibyte serial' => str_repeat('ÑSN-', 15),
    'very long' => trim(str_repeat('Stainless steel instrument trolley ', 10)),
    'very long unbroken' => str_repeat('X', 400),
])->with([
    'reference 90 × 55' => [[90.0, 55.0]],
    'small 70 × 42' => [[70.0, 42.0]],
    'default sheet 95 × 54' => [[95.0, 54.0]],
    'tiny 60 × 36' => [[60.0, 36.0]],
]);

it('keeps two lines within the row height', function (string $text, array $tag) {
    ['row' => $row, 'scale' => $scale] = fitterGeometry(...$tag);

    $fit = TagTextFitter::fitForTag($text, ...$tag);

    expect($fit['lines'])->toHaveCount(2)
        ->and($fit['size_pt'])->toBeLessThanOrEqual(max(fitterTwoLineMaxPt($row, $scale), TagTextFitter::MIN_PT * $scale))
        ->and(2 * $fit['size_pt'] * TagTextFitter::RENDERED_LINE_EM * TagTextFitter::PT_TO_MM)->toBeLessThanOrEqual($row);
})->with([
    'long with spaces' => 'Refrigerator, Two-door Inverter, Frost-free, 9.1 cu ft, with ice maker and water dispenser',
    'very long' => trim(str_repeat('Stainless steel instrument trolley ', 10)),
])->with([
    'reference 90 × 55' => [[90.0, 55.0]],
    'small 70 × 42' => [[70.0, 42.0]],
    'default sheet 95 × 54' => [[95.0, 54.0]],
]);

it('shrinks text that is slightly too long onto one line before wrapping it', function () {
    ['width' => $width] = fitterGeometry(90, 55);
    $text = str_repeat('W', fitterCapacity($width, TagTextFitter::MAX_PT) + 3);

    $fit = TagTextFitter::fitForTag($text, 90, 55);

    expect($fit['lines'])->toBe([$text])
        ->and($fit['size_pt'])->toBeLessThan(TagTextFitter::MAX_PT)
        ->and($fit['size_pt'])->toBeGreaterThan(fitterTwoLineMaxPt(TagTextFitter::rowHeightFor(55, 1.0), 1.0))
        ->and(mb_strlen($text))->toBeLessThanOrEqual(fitterCapacity($width, $fit['size_pt']));
});

it('cuts text too long even at the minimum size with an ellipsis filling line two', function () {
    $row = TagTextFitter::rowHeightFor(55, 1.0);
    $text = trim(str_repeat('Laboratory centrifuge accessory kit ', 8));

    $fit = TagTextFitter::fit($text, 30.0, 1.0, $row);
    $capacity = max(4, fitterCapacity(30.0, TagTextFitter::MIN_PT));
    $firstLine = TagTextFitter::wrap($text, $capacity)[0];
    $rest = implode(' ', array_slice(TagTextFitter::wrap($text, $capacity), 1));

    expect($fit['size_pt'])->toBe(TagTextFitter::MIN_PT)
        ->and($fit['lines'])->toBe([$firstLine, mb_substr($rest, 0, $capacity - 1).'…'])
        ->and(mb_strlen($fit['lines'][1]))->toBe($capacity);
});

it('hard-cuts an unbroken serial with the first line exactly as wide as the row allows', function () {
    $row = TagTextFitter::rowHeightFor(55, 1.0);
    $serial = str_repeat('AB12CD34EF', 5).'GH';
    $step = (new ReflectionClassConstant(TagTextFitter::class, 'STEP_PT'))->getValue();

    // The largest step from the two-line ceiling at which two lines hold the serial.
    $expected = fitterTwoLineMaxPt($row, 1.0);
    while (2 * max(4, fitterCapacity(30.0, $expected)) < mb_strlen($serial)) {
        $expected -= $step;
    }

    $fit = TagTextFitter::fit($serial, 30.0, 1.0, $row);

    expect(abs($fit['size_pt'] - $expected))->toBeLessThan(0.01)
        ->and(mb_strlen($fit['lines'][0]))->toBe(max(4, fitterCapacity(30.0, $expected)))
        ->and(implode('', $fit['lines']))->toBe($serial);
});

it('keeps multibyte text whole and counts it by character', function (string $text, float $width) {
    $row = TagTextFitter::rowHeightFor(55, 1.0);

    $fit = TagTextFitter::fit($text, $width, 1.0, $row);

    expect(mb_check_encoding(implode('', $fit['lines']), 'UTF-8'))->toBeTrue()
        ->and(str_replace(' ', '', implode('', $fit['lines'])))->toBe(str_replace(' ', '', $text));

    foreach ($fit['lines'] as $line) {
        expect(mb_strlen($line))->toBeLessThanOrEqual(fitterCapacity($width, $fit['size_pt']));
    }
})->with([
    'a name on one line' => ['Ma. Niña Peñaflorida-Ibáñez', 58.16],
    'a name wrapped' => ['Ma. Niña Peñaflorida-Ibáñez', 15.0],
    'an Ñ serial hard-cut' => [str_repeat('ÑSN-', 15), 30.0],
]);

it('puts a multibyte name that fits on one line at the largest size', function () {
    expect(TagTextFitter::fitForTag('Ma. Niña Peñaflorida-Ibáñez', 90, 55))
        ->toBe(['size_pt' => TagTextFitter::MAX_PT, 'lines' => ['Ma. Niña Peñaflorida-Ibáñez']]);
});

it('wraps at spaces and hard-cuts words longer than a line', function (string $text, int $width, array $expected) {
    expect(TagTextFitter::wrap($text, $width))->toBe($expected);
})->with([
    'breaks at the last space that fits' => ['Electric Fan Stand', 12, ['Electric Fan', 'Stand']],
    'collapses runs of whitespace' => ["Electric   Fan\t Stand", 20, ['Electric Fan Stand']],
    'a word exactly one line wide' => ['ABCDEFGHIJ', 10, ['ABCDEFGHIJ']],
    'a word one character too long' => ['ABCDEFGHIJK', 10, ['ABCDEFGHIJ', 'K']],
    'a long word after a short one shares its line' => ['abc ABCDEFGHIJKLMN', 10, ['abc ABCDEF', 'GHIJKLMN']],
    'a long word after a nearly full line starts a new one' => ['abcdefgh ABCDEFGHIJKLMN', 10, ['abcdefgh', 'ABCDEFGHIJ', 'KLMN']],
    'multibyte characters count once' => ['Ñañañaña Ñ', 10, ['Ñañañaña Ñ']],
]);
