<?php

namespace App\Models;

use App\Support\InventoryCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocations extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // The cached location list is rebuilt after any change.
        static::saved(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_STORAGE_LOCATIONS));
        static::deleted(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_STORAGE_LOCATIONS));
    }

    protected $fillable = [
        'name',
        'type',
    ];

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'storage_location_id');
    }
}
