<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use App\Services\Inventory\StockStatusMonitor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property InventoryTransactionType $type
 */
class InventoryTransactions extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'batch_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return ['type' => InventoryTransactionType::class];
    }

    protected static function booted(): void
    {
        // Every stock movement writes a transaction, so this one hook covers
        // receiving, issuance and adjustments.
        static::created(fn (self $transaction) => app(StockStatusMonitor::class)
            ->queue((int) $transaction->inventory_item_id));
    }

    /**
     * @return BelongsTo<InventoryItems, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<InventoryBatches, $this>
     */
    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatches::class, 'batch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
