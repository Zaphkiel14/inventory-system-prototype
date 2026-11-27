<?php

namespace App\Models;

use Database\Factories\RequisitionItemsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One requested item. Every quantity from each stage is preserved here:
 * requested (request), available snapshot (request), approved (approval),
 * released and outstanding (issuance).
 *
 * @property int $id
 * @property int $requisition_id
 * @property int $inventory_item_id
 * @property int $requested_qty
 * @property int $available_qty
 * @property int|null $approved_qty
 * @property int $released_qty
 * @property int $outstanding_qty
 * @property string|null $issue_remarks RIS issue-column remarks, set at issuance
 */
class RequisitionItems extends Model
{
    /** @use HasFactory<RequisitionItemsFactory> */
    use HasFactory;

    protected $fillable = [
        'requisition_id',
        'inventory_item_id',
        'requested_qty',
        'available_qty',
        'approved_qty',
        'released_qty',
        'outstanding_qty',
        'issue_remarks',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'integer',
            'available_qty' => 'integer',
            'approved_qty' => 'integer',
            'released_qty' => 'integer',
            'outstanding_qty' => 'integer',
        ];
    }

    /** @return BelongsTo<Requisitions, $this> */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    /** @return BelongsTo<InventoryItems, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }
}
