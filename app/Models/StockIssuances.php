<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIssuances extends Model
{
    protected $fillable = [
        'requisition_id',
        'issuance_number',
        'released_by',
        'received_by',
        'released_at',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    public function stockIssuanceItems(): HasMany
    {
        return $this->hasMany(StockIssuanceItems::class, 'stock_issuance_id');
    }
}
