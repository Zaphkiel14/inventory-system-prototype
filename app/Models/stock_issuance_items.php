<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stock_issuance_items extends Model
{
    protected $fillable = [
        'stock_issuance_id',
        'requisition_item_id',
        'released_qty',
    ];
}
