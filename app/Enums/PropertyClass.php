<?php

namespace App\Enums;

/**
 * How an issued unit is accounted for, decided by its item's property flag
 * and unit cost (see App\Services\Property\PropertyClassifier).
 */
enum PropertyClass: string
{
    case Ppe = 'ppe';
    case Sphv = 'sphv';
    case Splv = 'splv';
    case Consumable = 'consumable';

    public function label(): string
    {
        return match ($this) {
            self::Ppe => 'PPE',
            self::Sphv => 'Semi-expendable (High Value)',
            self::Splv => 'Semi-expendable (Low Value)',
            self::Consumable => 'Consumable',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ppe => 'indigo',
            self::Sphv => 'sky',
            self::Splv => 'teal',
            self::Consumable => 'zinc',
        };
    }
}
