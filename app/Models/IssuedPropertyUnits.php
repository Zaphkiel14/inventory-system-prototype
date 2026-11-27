<?php

namespace App\Models;

use App\Enums\PropertyClass;
use Database\Factories\IssuedPropertyUnitsFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A physical property unit released at issuance (brand, model, serial),
 * linked to the batch draw it came from. Written by IssuancesForm only;
 * ICS/PAR lines point at these rows.
 *
 * @property string|null $serial_number as entered
 * @property string|null $serial_number_normalized lowercased and trimmed; carries the unique index
 * @property PropertyClass $property_class
 * @property string $unit_cost
 */
class IssuedPropertyUnits extends Model
{
    /** @use HasFactory<IssuedPropertyUnitsFactory> */
    use HasFactory;

    protected $fillable = [
        'stock_issuance_batch_id',
        'inventory_item_id',
        'brand',
        'model',
        'serial_number',
        'unit_cost',
        'property_class',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $unit): void {
            $unit->serial_number_normalized = self::normalizeSerial($unit->serial_number);
        });
    }

    /**
     * The form of a serial number that uniqueness is checked on: trimmed and
     * lowercased, or null when empty.
     */
    public static function normalizeSerial(?string $serial): ?string
    {
        $serial = mb_strtolower(trim((string) $serial));

        return $serial === '' ? null : $serial;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'property_class' => PropertyClass::class,
        ];
    }

    /**
     * @return BelongsTo<StockIssuanceBatches, $this>
     */
    public function stockIssuanceBatch(): BelongsTo
    {
        return $this->belongsTo(StockIssuanceBatches::class, 'stock_issuance_batch_id');
    }

    /**
     * @return BelongsTo<InventoryItems, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    /**
     * The ICS/PAR line this unit is on (at most one).
     *
     * @return HasOne<PropertyDocumentLines, $this>
     */
    public function documentLine(): HasOne
    {
        return $this->hasOne(PropertyDocumentLines::class, 'issued_property_unit_id');
    }

    /**
     * Units not yet on any ICS/PAR line.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithoutDocument(Builder $query): void
    {
        $query->whereDoesntHave('documentLine');
    }

    /**
     * Units released by the given issuance.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForIssuance(Builder $query, int $stockIssuanceId): void
    {
        $query->whereHas('stockIssuanceBatch.stockIssuanceItem', fn (Builder $q) => $q->where('stock_issuance_id', $stockIssuanceId));
    }
}
