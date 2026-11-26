<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One requested item. Every quantity from each stage is preserved here:
 * requested (request), available snapshot (request), approved (approval),
 * released and outstanding (issuance).
 */
class RequisitionItems extends Model
{
    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'requested_qty',
        'available_qty',
        'approved_qty',
        'released_qty',
        'outstanding_qty',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'integer',
            'available_qty' => 'integer',
            'approved_qty' => 'integer',
            'released_qty' => 'integer',
            'outstanding_qty' => 'integer',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    /** @return BelongsTo<InventoryItems, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }
}
