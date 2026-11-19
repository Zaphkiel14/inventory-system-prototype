<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * A release of stock against an approved requisition. Releases move stock out
 * of batches, so they are permanent; returns go through stock adjustments.
 */
class StockIssuances extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'issuance_number',
        'released_by',
        'received_by',
        'released_at',
        'remarks',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Issuances are permanent and cannot be modified.'));
        static::deleting(fn () => throw new LogicException('Issuances are permanent and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function stockIssuanceItems(): HasMany
    {
        return $this->hasMany(StockIssuanceItems::class, 'stock_issuance_id');
    }
}
