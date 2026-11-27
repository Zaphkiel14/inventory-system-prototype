<?php

namespace App\Services\Property;

/**
 * Chooses the font size and line breaks for a property tag value, so it fits
 * its row in dompdf (which can't shrink text to fit). Ported unchanged from
 * resources/views/pdf/partials/property-tag.blade.php:
 *
 *  - everything scales from a 90 × 55 mm reference tag;
 *  - one line may use up to 7.5 pt; two lines must both fit the row height,
 *    budgeting 1.4 em per rendered line; never below 3.8 pt;
 *  - character width is estimated at 0.62 em;
 *  - lines break at spaces where possible, otherwise hard-cut (serials and
 *    property numbers have no spaces);
 *  - text too long even at the minimum size is cut with "…" at the end of
 *    line two;
 *  - the returned size is floored to 0.01 pt (never rounded up), so it may
 *    sit up to 0.01 pt under MIN_PT × scale but never spills.
 *
 * Pure: no I/O.
 */
class TagTextFitter
{
    public const REFERENCE_WIDTH_MM = 90;

    public const REFERENCE_HEIGHT_MM = 55;

    public const MAX_PT = 7.5;

    public const MIN_PT = 3.8;

    public const RENDERED_LINE_EM = 1.4;

    public const CHAR_WIDTH_EM = 0.62;

    /** Width of the label column, as a percentage of the table. */
    public const LABEL_PERCENT = 32;

    public const ROW_COUNT = 7;

    public const PT_TO_MM = 0.3528;

    private const STEP_PT = 0.25;

    public static function scaleFor(float $widthMm, float $heightMm): float
    {
        return min($widthMm / self::REFERENCE_WIDTH_MM, $heightMm / self::REFERENCE_HEIGHT_MM);
    }

    /**
     * Width of the value column in mm (table inside the side margins, minus
     * the label column and the value padding).
     */
    public static function valueWidthFor(float $widthMm, float $scale): float
    {
        $tableWidth = $widthMm - 3 * $scale;

        return max($tableWidth * (100 - self::LABEL_PERCENT) / 100 - 1 * $scale, 10);
    }

    /**
     * Height of one of the seven rows in mm (after the header and bottom margin).
     */
    public static function rowHeightFor(float $heightMm, float $scale): float
    {
        return ($heightMm - 15 * $scale - 3 * $scale) / self::ROW_COUNT;
    }

    /**
     * fit() for a tag of the given size, deriving scale, value width and row
     * height exactly as the partial does.
     *
     * @return array{size_pt: float, lines: list<string>}
     */
    public static function fitForTag(?string $text, float $widthMm, float $heightMm): array
    {
        $scale = self::scaleFor($widthMm, $heightMm);

        return self::fit($text, self::valueWidthFor($widthMm, $scale), $scale, self::rowHeightFor($heightMm, $scale));
    }

    /**
     * Font size (pt) and the value's lines: one or two, none for empty text.
     *
     * @return array{size_pt: float, lines: list<string>}
     */
    public static function fit(?string $text, float $valueWidthMm, float $scale, float $rowHeightMm): array
    {
        $maxPt = self::MAX_PT * $scale;
        $minPt = self::MIN_PT * $scale;
        $twoLineMaxPt = min($maxPt, $rowHeightMm / (2 * self::RENDERED_LINE_EM * self::PT_TO_MM));

        $text = trim((string) $text);

        if ($text === '') {
            return ['size_pt' => $maxPt, 'lines' => []];
        }

        $perLine = fn (float $size): int => max(4, (int) floor($valueWidthMm / ($size * self::CHAR_WIDTH_EM * self::PT_TO_MM)));

        for ($size = $maxPt; $size >= $minPt; $size -= self::STEP_PT) {
            if (mb_strlen($text) <= $perLine($size)) {
                return ['size_pt' => self::floorPt($size), 'lines' => [$text]];
            }
        }

        for ($size = max($twoLineMaxPt, $minPt); $size >= $minPt; $size -= self::STEP_PT) {
            $lines = self::wrap($text, $perLine($size));

            if (count($lines) <= 2) {
                return ['size_pt' => self::floorPt($size), 'lines' => $lines];
            }
        }

        $width = $perLine($minPt);
        $lines = self::wrap($text, $width);
        $second = implode(' ', array_slice($lines, 1));

        return ['size_pt' => self::floorPt($minPt), 'lines' => [$lines[0], mb_substr($second, 0, $width - 1).'…']];
    }

    /**
     * Round a fitted size down to 0.01 pt, never up: a size rounded up could
     * overflow the row height or the line's character capacity. Rounded to 6
     * places first so float noise (3.8 × 100 = 379.99…) doesn't drop a step.
     */
    public static function floorPt(float $size): float
    {
        return floor(round($size * 100, 6)) / 100;
    }

    /**
     * Break text into lines of at most $width characters: at spaces where
     * possible, otherwise hard-cut.
     *
     * @return list<string>
     */
    public static function wrap(string $text, int $width): array
    {
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            while (mb_strlen($word) > $width) {
                $room = $line === '' ? $width : $width - mb_strlen($line) - 1;

                if ($room < 4) {
                    $lines[] = $line;
                    $line = '';
                    $room = $width;
                }

                $lines[] = ($line === '' ? '' : $line.' ').mb_substr($word, 0, $room);
                $word = mb_substr($word, $room);
                $line = '';
            }

            if ($line === '') {
                $line = $word;
            } elseif (mb_strlen($line) + 1 + mb_strlen($word) <= $width) {
                $line .= ' '.$word;
            } else {
                $lines[] = $line;
                $line = $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }
}
