<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stock_issuances extends Model
{
    protected $fillable = [
        'requisition_id',
        'issuance_number',
        'released_by',
        'received_by',
        'released_at',
    ];
}
