<?php

namespace App\Models;

use App\Support\InventoryCache;
use Database\Factories\SuppliersFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suppliers extends Model
{
    /** @use HasFactory<SuppliersFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // The cached supplier list is rebuilt after any change.
        static::saved(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_SUPPLIERS));
        static::deleted(fn () => InventoryCache::forgetLookup(InventoryCache::LOOKUP_SUPPLIERS));
    }

    protected $fillable = [
        'name',
        'contact_person',
        'contact',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Suppliers that can be picked for new deliveries.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return HasMany<StockReceipts, $this>
     */
    public function stockReceipts(): HasMany
    {
        return $this->hasMany(StockReceipts::class, 'supplier_id');
    }
}
