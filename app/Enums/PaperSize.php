<?php

namespace App\Enums;

/**
 * Paper size for printed forms; values are the dompdf paper names.
 */
enum PaperSize: string
{
    case A4 = 'a4';
    case Letter = 'letter';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'A4 (210 × 297 mm)',
            self::Letter => 'Letter (8.5 × 11 in)',
            self::Legal => 'Legal (8.5 × 14 in)',
        };
    }

    public function color(): string
    {
        return 'zinc';
    }
}
