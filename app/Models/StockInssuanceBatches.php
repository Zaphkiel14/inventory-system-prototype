<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockInssuanceBatches extends Model
{
    protected $fillable = [
        'stock_issuance_item_id',
        'batch_id',
        'quantity',
    ];
}
