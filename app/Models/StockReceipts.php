<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReceipts extends Model
{
    protected $fillable = [
        'supplier_id',
        'received_by',
        'reference_number',
        'received_date',
        'remarks',
    ];
}
