<?php

namespace App\Enums;

/**
 * How an item's usable stock compares to its thresholds, most urgent first.
 */
enum StockStatus: string
{
    case Out = 'out';
    case Critical = 'critical';
    case Reorder = 'reorder';
    case Healthy = 'healthy';

    public static function for(int $available, int $minimumStock, int $reorderLevel): self
    {
        return match (true) {
            $available <= 0 => self::Out,
            $available <= $minimumStock => self::Critical,
            $available <= $reorderLevel => self::Reorder,
            default => self::Healthy,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Out => 'Out of stock',
            self::Critical => 'Critical',
            self::Reorder => 'Reorder',
            self::Healthy => 'Healthy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Out => 'red',
            self::Critical => 'orange',
            self::Reorder => 'amber',
            self::Healthy => 'green',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Out => 'No usable stock',
            self::Critical => 'At or below minimum stock',
            self::Reorder => 'At or below reorder level',
            self::Healthy => 'Above reorder level',
        };
    }

    /**
     * Sort weight: lower is more urgent.
     */
    public function urgency(): int
    {
        return array_search($this, self::cases(), true);
    }

    public function needsReorder(): bool
    {
        return $this !== self::Healthy;
    }
}
