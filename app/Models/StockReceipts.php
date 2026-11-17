<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReceipts extends Model
{
    protected $fillable = [
        'supplier_id',
        'received_by',
        'reference_number',
        'received_date',
        'remarks',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'stock_receipt_id');
    }
}
