<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suppliers extends Model
{
    protected $fillable = [
        'name',
        'contact',
    ];

    public function stockReceipts(): HasMany
    {
        return $this->hasMany(StockReceipts::class, 'supplier_id');
    }
}
