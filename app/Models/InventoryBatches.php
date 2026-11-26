<?php

namespace App\Models;

use App\Support\InventoryCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatches extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'stock_receipt_id',
        'storage_location_id',
        'batch_number',
        'lot_number',
        'received_qty',
        'remaining_qty',
        'manufactured_date',
        'expiration_date',
        'received_at',
        'unit_cost',
    ];

    protected static function booted(): void
    {
        // Batch edits (location, expiry, quantities) feed the cached stock
        // figures even when no ledger row is written.
        static::saved(fn () => InventoryCache::bumpLedger());
        static::deleted(fn () => InventoryCache::bumpLedger());
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    public function stockReceipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipts::class, 'stock_receipt_id');
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocations::class, 'storage_location_id');
    }

    /**
     * Batches that can still be dispensed: stock left and not expired.
     *
     * Expiry filters compare the raw column (no whereDate) so the index can be
     * used. Stored values may be "Y-m-d" or "Y-m-d 00:00:00", so bounds are
     * always written as ">= day" / "< day", which match both formats.
     */
    public function scopeUsable(Builder $query): void
    {
        $query->where('remaining_qty', '>', 0)
            ->where(fn (Builder $q) => $q
                ->whereNull('expiration_date')
                ->orWhere('expiration_date', '>=', today()->toDateString()));
    }

    /**
     * Batches past their expiry date that still hold stock (needs write-off).
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('remaining_qty', '>', 0)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', today()->toDateString());
    }

    /**
     * Batches with stock that expire between today and $days from now (inclusive).
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->where('remaining_qty', '>', 0)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', today()->toDateString())
            ->where('expiration_date', '<', today()->addDays($days + 1)->toDateString());
    }

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustments::class, 'batch_id');
    }

    protected function casts(): array
    {
        return [
            'manufactured_date' => 'date',
            'expiration_date' => 'date',
            'received_at' => 'datetime',
            'unit_cost' => 'decimal:2',
        ];
    }
}
