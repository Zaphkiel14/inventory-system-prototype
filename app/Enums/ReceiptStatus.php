<?php

namespace App\Enums;

enum ReceiptStatus: string
{
    case Complete = 'complete_delivery';
    case Partial = 'partial_delivery';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Fully Delivered',
            self::Partial => 'Partial Delivery',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Complete => 'green',
            self::Partial => 'amber',
        };
    }
}
