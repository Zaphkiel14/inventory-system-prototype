<?php

namespace App\Models;

use App\Support\InventoryCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryCategories extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // The cached category list is rebuilt after any change.
        static::saved(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_CATEGORIES));
        static::deleted(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_CATEGORIES));
    }

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryItems::class,
            'inventory_category_item',
            'inventory_category_id',
            'inventory_item_id',
        )->withTimestamps();
    }
}
