<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryCategories extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryItems::class,
            'inventory_category_inventory_item',
            'inventory_category_id',
            'inventory_item_id',
        )->withTimestamps();
    }
}
