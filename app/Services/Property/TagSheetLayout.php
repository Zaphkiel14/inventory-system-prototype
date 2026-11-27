<?php

namespace App\Services\Property;

use App\Models\StickerSheetProfiles;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Places property tags on sticker sheets, in millimetres. Slots fill across,
 * then down; the first sheet starts at a chosen slot (to use up a partly
 * used sheet) and later sheets start at slot 1. The profile's calibration
 * offsets are added to every position.
 *
 * Arithmetic is done in hundredths of a millimetre (the profiles store 2
 * decimals), so positions don't drift across a sheet.
 */
class TagSheetLayout
{
    /**
     * @return list<list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}>>
     */
    public static function place(StickerSheetProfiles $profile, int $count, int $startSlot = 1): array
    {
        $perSheet = self::slotsPerSheet($profile);

        if ($startSlot < 1 || $startSlot > $perSheet) {
            throw new InvalidArgumentException("The starting slot must be between 1 and {$perSheet}.");
        }

        if ($count < 0) {
            throw new InvalidArgumentException('The number of tags cannot be negative.');
        }

        $sheets = [];
        $sheet = [];
        $slot = $startSlot;

        for ($placed = 0; $placed < $count; $placed++) {
            if ($slot > $perSheet) {
                $sheets[] = $sheet;
                $sheet = [];
                $slot = 1;
            }

            $sheet[] = self::slot($profile, $slot++);
        }

        if ($sheet !== []) {
            $sheets[] = $sheet;
        }

        return $sheets;
    }

    /**
     * Every slot of one sheet, for the calibration page.
     *
     * @return list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}>
     */
    public static function calibration(StickerSheetProfiles $profile): array
    {
        return self::place($profile, self::slotsPerSheet($profile))[0] ?? [];
    }

    public static function slotsPerSheet(StickerSheetProfiles $profile): int
    {
        return max(0, $profile->columns) * max(0, $profile->rows);
    }

    /**
     * Whether the grid (margins, tags and gaps) fits on the page and every
     * dimension is usable. Calibration offsets are not counted: they correct
     * the printer, not the layout.
     */
    public static function fits(StickerSheetProfiles $profile): bool
    {
        $mm = fn (mixed $value): int => Money::centavos((string) $value);

        if ($profile->columns < 1 || $profile->rows < 1
            || $mm($profile->tag_width_mm) <= 0 || $mm($profile->tag_height_mm) <= 0
            || $mm($profile->margin_left_mm) < 0 || $mm($profile->margin_top_mm) < 0
            || $mm($profile->gap_x_mm) < 0 || $mm($profile->gap_y_mm) < 0) {
            return false;
        }

        $width = $mm($profile->margin_left_mm)
            + $profile->columns * $mm($profile->tag_width_mm)
            + ($profile->columns - 1) * $mm($profile->gap_x_mm);

        $height = $mm($profile->margin_top_mm)
            + $profile->rows * $mm($profile->tag_height_mm)
            + ($profile->rows - 1) * $mm($profile->gap_y_mm);

        return $width <= $mm($profile->page_width_mm) && $height <= $mm($profile->page_height_mm);
    }

    /**
     * @return array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}
     */
    private static function slot(StickerSheetProfiles $profile, int $index): array
    {
        $mm = fn (mixed $value): int => Money::centavos((string) $value);

        $row = intdiv($index - 1, $profile->columns) + 1;
        $column = ($index - 1) % $profile->columns + 1;

        $x = $mm($profile->margin_left_mm) + ($column - 1) * ($mm($profile->tag_width_mm) + $mm($profile->gap_x_mm)) + $mm($profile->offset_x_mm);
        $y = $mm($profile->margin_top_mm) + ($row - 1) * ($mm($profile->tag_height_mm) + $mm($profile->gap_y_mm)) + $mm($profile->offset_y_mm);

        return [
            'index' => $index,
            'row' => $row,
            'column' => $column,
            'x_mm' => $x / 100.0,
            'y_mm' => $y / 100.0,
            'width_mm' => $mm($profile->tag_width_mm) / 100.0,
            'height_mm' => $mm($profile->tag_height_mm) / 100.0,
        ];
    }
}
