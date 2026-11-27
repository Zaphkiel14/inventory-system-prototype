<?php

namespace App\Enums;

enum PropertyTagStatus: string
{
    case Pending = 'pending';
    case Printed = 'printed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Printed => 'Printed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Printed => 'green',
        };
    }
}
