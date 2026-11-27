<?php

namespace App\Enums;

enum PropertyDocumentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Returned = 'returned';
    case Transferred = 'transferred';
    case Renewed = 'renewed';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Returned => 'Returned',
            self::Transferred => 'Transferred',
            self::Renewed => 'Renewed',
            self::Disposed => 'Disposed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Active => 'green',
            self::Returned => 'amber',
            self::Transferred => 'sky',
            self::Renewed => 'indigo',
            self::Disposed => 'red',
        };
    }
}
