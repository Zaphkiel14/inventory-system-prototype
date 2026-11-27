<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\StockIssuancesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * A release of stock against an approved requisition. Releases move stock out
 * of batches, so they are permanent; returns go through stock adjustments.
 *
 * @property int $id
 * @property int $requisition_id
 * @property int|null $user_id
 * @property string $issuance_number
 * @property string $released_by
 * @property string|null $released_by_designation
 * @property string $received_by
 * @property string|null $received_by_designation
 * @property CarbonImmutable $released_at
 * @property string|null $remarks
 */
class StockIssuances extends Model
{
    /** @use HasFactory<StockIssuancesFactory> */
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'user_id',
        'issuance_number',
        'released_by',
        'received_by',
        'released_at',
        'remarks',
        'released_by_designation',
        'received_by_designation',
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

    /** @return BelongsTo<Requisitions, $this> */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<StockIssuanceItems, $this> */
    public function stockIssuanceItems(): HasMany
    {
        return $this->hasMany(StockIssuanceItems::class, 'stock_issuance_id');
    }

    /**
     * Property units released by this issuance that are not on an ICS/PAR line yet.
     */
    public function pendingPropertyUnitsCount(): int
    {
        return IssuedPropertyUnits::query()->forIssuance($this->id)->withoutDocument()->count();
    }
}
