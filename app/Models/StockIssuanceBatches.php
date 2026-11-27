<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIssuanceBatches extends Model
{
    protected $fillable = [
        'stock_issuance_item_id',
        'batch_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    /** @return BelongsTo<StockIssuanceItems, $this> */
    public function stockIssuanceItem(): BelongsTo
    {
        return $this->belongsTo(StockIssuanceItems::class, 'stock_issuance_item_id');
    }

    /** @return BelongsTo<InventoryBatches, $this> */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }

    /**
     * Property units released from this batch draw (property items only).
     *
     * @return HasMany<IssuedPropertyUnits, $this>
     */
    public function issuedPropertyUnits(): HasMany
    {
        return $this->hasMany(IssuedPropertyUnits::class, 'stock_issuance_batch_id');
    }
}
