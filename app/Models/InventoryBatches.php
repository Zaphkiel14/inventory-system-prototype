<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
