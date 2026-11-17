<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransactions extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'batch_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
    ];
}
