<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIssuanceItems extends Model
{
    protected $fillable = [
        'stock_issuance_id',
        'requisition_item_id',
        'released_qty',
    ];

    protected function casts(): array
    {
        return ['released_qty' => 'integer'];
    }

    /** @return BelongsTo<StockIssuances, $this> */
    public function stockIssuance(): BelongsTo
    {
        return $this->belongsTo(StockIssuances::class, 'stock_issuance_id');
    }

    /** @return BelongsTo<RequisitionItems, $this> */
    public function requisitionItem(): BelongsTo
    {
        return $this->belongsTo(RequisitionItems::class, 'requisition_item_id');
    }

    /**
     * Which batches this line was drawn from, and how much from each.
     *
     * @return HasMany<StockIssuanceBatches, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(StockIssuanceBatches::class, 'stock_issuance_item_id');
    }
}
