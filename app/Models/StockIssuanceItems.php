<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockIssuanceItems extends Model
{
    protected $fillable = [
        'stock_issuance_id',
        'requisition_item_id',
        'released_qty',
    ];

    public function stockIssuance(): BelongsTo
    {
        return $this->belongsTo(StockIssuances::class, 'stock_issuance_id');
    }

    public function requisitionItem(): BelongsTo
    {
        return $this->belongsTo(RequisitionItems::class, 'requisition_item_id');
    }
}
