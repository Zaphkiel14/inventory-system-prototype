<?php

namespace App\Enums;

enum PropertyDocumentType: string
{
    case Ics = 'ics';
    case Par = 'par';

    public function label(): string
    {
        return match ($this) {
            self::Ics => 'Inventory Custodian Slip',
            self::Par => 'Property Acknowledgement Receipt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ics => 'sky',
            self::Par => 'indigo',
        };
    }
}
