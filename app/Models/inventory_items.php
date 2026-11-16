<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class inventory_items extends Model
{
    protected $fillable = [
        'inventory_category_id',
        'item_code',
        'name',
        'unit',
        'allocation_method',
        'requires_expiry',
        'minimum_stock',
        'reorder_level',
        'is_active',
    ];
}
