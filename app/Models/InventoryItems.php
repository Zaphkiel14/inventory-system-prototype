<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use App\Enums\StockStatus;
use App\Services\Inventory\StockStatusMonitor;
use App\Support\InventoryCache;
use Database\Factories\InventoryItemsFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property StockStatus|null $stock_status last known status, compared against to send stock alerts
 */
class InventoryItems extends Model
{
    /** @use HasFactory<InventoryItemsFactory> */
    use HasFactory;

    protected $fillable = [
        'inventory_category_id',
        'item_code',
        'name',
        'unit',
        'requires_expiry',
        'minimum_stock',
        'reorder_level',
        'is_active',
        'stock_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock_status' => StockStatus::class,
        ];
    }

    protected static function booted(): void
    {
        // A new item has no stock yet. Out is its silent baseline, so the
        // first receipt is an improvement and never sends an alert.
        static::creating(function (InventoryItems $item): void {
            $item->stock_status ??= StockStatus::Out;
        });

        // New thresholds (or reactivating an item) can change its status
        // without any stock moving.
        static::updated(function (InventoryItems $item): void {
            if ($item->wasChanged(['minimum_stock', 'reorder_level', 'is_active'])) {
                app(StockStatusMonitor::class)->queue($item->id);
            }
        });

        // Name, thresholds and the active flag feed the cached stock figures.
        static::saved(fn () => InventoryCache::bumpLedger());
        static::deleted(fn () => InventoryCache::bumpLedger());
    }

    /**
     * @return BelongsToMany<InventoryCategories, $this>
     */
    public function inventoryCategory(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryCategories::class,
            'inventory_category_item',
            'inventory_item_id',      // this model's key on the pivot
            'inventory_category_id',  // the related model's key on the pivot
        )->withTimestamps();
    }

    /**
     * @return HasMany<InventoryBatches, $this>
     */
    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'inventory_item_id');
    }

    /**
     * Adds `available_qty`: stock in usable (unexpired, non-empty) batches.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithAvailableQty(Builder $query): void
    {
        $query->withSum(
            ['inventoryBatches as available_qty' => fn (Builder $q) => $q->usable()],
            'remaining_qty',
        );
    }

    /**
     * Adds `committed_qty`: approved quantities not yet released. That stock
     * is still on the shelf but already promised to another requisition.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithCommittedQty(Builder $query): void
    {
        $query->withSum(
            ['requisitionItems as committed_qty' => fn (Builder $q) => $q
                ->where('outstanding_qty', '>', 0)
                ->whereHas('requisition', fn (Builder $r) => $r->whereIn('status', [
                    RequisitionStatus::Approved,
                    RequisitionStatus::PartiallyIssued,
                ]))],
            'outstanding_qty',
        );
    }

    /**
     * @return HasMany<RequisitionItems, $this>
     */
    public function requisitionItems(): HasMany
    {
        return $this->hasMany(RequisitionItems::class, 'inventory_item_id');
    }

    /**
     * @return HasMany<InventoryTransactions, $this>
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransactions::class, 'inventory_item_id');
    }
}
