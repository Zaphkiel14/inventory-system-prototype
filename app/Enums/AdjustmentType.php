<?php

namespace App\Enums;

enum AdjustmentType: string
{
    case Damage = 'damage';
    case Loss = 'loss';
    case ExpiryWriteOff = 'expiry_write_off';
    case CountCorrection = 'count_correction';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Damage => 'Damage',
            self::Loss => 'Loss',
            self::ExpiryWriteOff => 'Expiry Write-off',
            self::CountCorrection => 'Count Correction',
            self::Return => 'Return',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Damage => 'orange',
            self::Loss => 'red',
            self::ExpiryWriteOff => 'amber',
            self::CountCorrection => 'sky',
            self::Return => 'green',
        };
    }

    /**
     * How the entered quantity affects the batch:
     *  - decrease: quantity is removed from the batch
     *  - increase: quantity is added back to the batch
     *  - set:      quantity is the actual physical count
     */
    public function direction(): string
    {
        return match ($this) {
            self::Damage, self::Loss, self::ExpiryWriteOff => 'decrease',
            self::Return => 'increase',
            self::CountCorrection => 'set',
        };
    }

    public function quantityLabel(): string
    {
        return match ($this->direction()) {
            'decrease' => 'Quantity to remove',
            'increase' => 'Quantity returned to stock',
            'set' => 'Actual counted quantity',
        };
    }
}
