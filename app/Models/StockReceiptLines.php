<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $stock_receipt_id
 * @property int $inventory_item_id
 * @property int $ordered_qty
 * @property-read StockReceipts $receipt
 * @property-read InventoryItems $inventoryItem
 */
class StockReceiptLines extends Model
{
    protected $fillable = ['stock_receipt_id', 'inventory_item_id', 'ordered_qty'];

    protected function casts(): array
    {
        return ['ordered_qty' => 'integer'];
    }

    /** @return BelongsTo<StockReceipts, $this> */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipts::class, 'stock_receipt_id');
    }

    /** @return BelongsTo<InventoryItems, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }
}
