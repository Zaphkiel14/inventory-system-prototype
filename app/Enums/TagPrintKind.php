<?php

namespace App\Enums;

/**
 * What a property_tag_prints row records: a tag's first print, a reprint
 * (needs a reason), or a sheet with no tag (blank stickers, calibration).
 */
enum TagPrintKind: string
{
    case First = 'first';
    case Reprint = 'reprint';
    case Blank = 'blank';
    case Calibration = 'calibration';

    public function label(): string
    {
        return match ($this) {
            self::First => 'First print',
            self::Reprint => 'Reprint',
            self::Blank => 'Blank stickers',
            self::Calibration => 'Calibration page',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::First => 'green',
            self::Reprint => 'amber',
            self::Blank => 'zinc',
            self::Calibration => 'sky',
        };
    }
}
