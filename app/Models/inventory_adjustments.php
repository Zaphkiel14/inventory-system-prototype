<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class inventory_adjustments extends Model
{
    protected $fillable = [
        'batch_id',
        'user_id',
        'approved_id',
        'previous_qty',
        'changed_qty',
        'new_qty',
        'reason',
    ];
}
