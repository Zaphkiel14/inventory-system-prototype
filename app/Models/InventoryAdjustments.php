<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustments extends Model
{
    protected $fillable = [
        'batch_id',
        'user_id',
        'approved_id',
        'previous_qty',
        'changed_qty',
        'new_qty',
        'reason',
    ];

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }
}
