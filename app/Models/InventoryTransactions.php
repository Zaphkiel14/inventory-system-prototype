<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransactions extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'batch_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
