<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatches extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'stock_receipt_id',
        'storage_location_id',
        'batch_number',
        'received_qty',
        'remaining_qty',
        'manufactured_date',
        'expiration_date',
        'received_at',
        'unit_cost',
    ];

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

    public function inventoryAdjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustments::class, 'batch_id');
    }
}
