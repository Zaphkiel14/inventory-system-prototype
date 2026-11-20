<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItems extends Model
{
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
    ];

    public function inventoryCategory(): BelongsToMany
    {
        return $this->belongsToMany(
            InventoryCategories::class,
            'inventory_category_item',
            'inventory_item_id',      // this model's key on the pivot
            'inventory_category_id',  // the related model's key on the pivot
        )->withTimestamps();
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'inventory_item_id');
    }

    /**
     * Adds `available_qty`: stock in usable (unexpired, non-empty) batches.
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

    public function requisitionItems(): HasMany
    {
        return $this->hasMany(RequisitionItems::class, 'inventory_item_id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransactions::class, 'inventory_item_id');
    }
}
