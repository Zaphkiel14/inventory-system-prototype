<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class requisition_items extends Model
{
    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'requested_qty',
        'approved_qty',
        'released_qty',
    ];
}
