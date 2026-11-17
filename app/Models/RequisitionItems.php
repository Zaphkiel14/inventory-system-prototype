<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItems extends Model
{
    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'requested_qty',
        'approved_qty',
        'released_qty',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }
}
