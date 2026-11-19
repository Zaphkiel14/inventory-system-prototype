<?php

namespace App\Enums;

enum InventoryTransactionType: int
{
    case Receipt = 1;
    case Issuance = 2;
    case Adjustment = 3;

    public function label(): string
    {
        return match ($this) {
            self::Receipt => 'Receipt',
            self::Issuance => 'Issuance',
            self::Adjustment => 'Adjustment',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Receipt => 'green',
            self::Issuance => 'sky',
            self::Adjustment => 'amber',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Receipt => 'arrow-down-tray',
            self::Issuance => 'arrow-up-tray',
            self::Adjustment => 'adjustments-horizontal',
        };
    }

    /**
     * URL-friendly key used by filters (e.g. ?type=receipt).
     */
    public function key(): string
    {
        return strtolower($this->name);
    }

    public static function fromKey(string $key): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->key() === $key) {
                return $case;
            }
        }

        return null;
    }
}
