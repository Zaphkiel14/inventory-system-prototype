<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stock_inssuance_batches extends Model
{
    protected $fillable = [
        'stock_issuance_item_id',
        'batch_id',
        'quantity',
    ];
}
