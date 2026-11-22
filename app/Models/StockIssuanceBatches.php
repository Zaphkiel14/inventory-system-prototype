<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function stockIssuanceItem(): BelongsTo
    {
        return $this->belongsTo(StockIssuanceItems::class, 'stock_issuance_item_id');
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }
}
