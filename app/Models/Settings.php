<?php

namespace App\Models;

use App\Support\InventoryCache;
use Illuminate\Database\Eloquent\Model;

/**
 * One application setting. Read and written through typed accessors such as
 * App\Support\PropertySettings, never directly.
 *
 * @property string $key
 * @property mixed $value
 */
class Settings extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_SETTINGS));
        static::deleted(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_SETTINGS));
    }
}
