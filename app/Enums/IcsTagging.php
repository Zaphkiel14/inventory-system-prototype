<?php

namespace App\Enums;

/**
 * Which ICS lines get a property tag when the ICS is finalized. PAR lines
 * are always tagged.
 */
enum IcsTagging: string
{
    case All = 'all';
    case SphvOnly = 'sphv_only';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All ICS items',
            self::SphvOnly => 'High-value (SPHV) only',
            self::None => 'No ICS items',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::All => 'green',
            self::SphvOnly => 'sky',
            self::None => 'zinc',
        };
    }

    /**
     * Whether an ICS line of the given class gets a tag under this setting.
     */
    public function tags(PropertyClass $class): bool
    {
        return match ($this) {
            self::All => true,
            self::SphvOnly => $class === PropertyClass::Sphv,
            self::None => false,
        };
    }
}
