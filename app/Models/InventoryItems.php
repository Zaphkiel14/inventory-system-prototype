<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItems extends Model
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

    public function inventoryCategory(): BelongsToMany
    {
        return $this->belongsToMany(InventoryCategories::class, 'inventory_category_inventory_item')->withTimeStamps();
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'inventory_item_id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransactions::class, 'inventory_item_id');
    }
}
