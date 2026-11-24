<?php

namespace App\Models;

use App\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class StockReceipts extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'purchase_order_number',
        'received_by',
        'reference_number',
        'received_date',
        'delivery_date',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'datetime',
            'delivery_date' => 'datetime',
            'status' => ReceiptStatus::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatches::class, 'stock_receipt_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachments::class, 'attachable');
    }
}
