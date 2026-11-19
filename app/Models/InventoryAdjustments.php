<?php

namespace App\Models;

use App\Enums\AdjustmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Adjustments are an append-only audit trail: once recorded they can never be
 * edited or deleted. Mistakes are fixed by recording a new adjustment.
 */
class InventoryAdjustments extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'user_id',
        'type',
        'requested_by',
        'approved_by',
        'previous_qty',
        'changed_qty',
        'new_qty',
        'reason',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Inventory adjustments are permanent and cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException('Inventory adjustments are permanent and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'type' => AdjustmentType::class,
            'previous_qty' => 'integer',
            'changed_qty' => 'integer',
            'new_qty' => 'integer',
        ];
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
