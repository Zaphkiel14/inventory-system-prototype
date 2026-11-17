<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocations extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'storage_location_id');
    }
}
