<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCategories extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItems::class, 'category_id');
    }
}
