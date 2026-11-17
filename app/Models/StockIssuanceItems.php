<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIssuanceItems extends Model
{
    protected $fillable = [
        'stock_issuance_id',
        'requisition_item_id',
        'released_qty',
    ];
}
