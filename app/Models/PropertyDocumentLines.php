<?php

namespace App\Models;

use App\Enums\PropertyClass;
use App\Models\Concerns\Auditable;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\PropertyDocumentLinesFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * One line of an ICS or PAR. Usually one issued property unit; hand-encoded
 * lines for existing property have none. total_cost is computed from
 * quantity × unit_cost and never stored.
 *
 * @property int $id
 * @property int $property_document_id
 * @property int|null $issued_property_unit_id
 * @property int|null $inventory_item_id
 * @property int $quantity
 * @property string|null $unit
 * @property string $unit_cost
 * @property string $item_name
 * @property string|null $item_type
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $property_number
 * @property int|null $estimated_useful_life_years
 * @property CarbonImmutable|null $date_acquired
 * @property-read string $total_cost two-decimal amount, e.g. "2392.22"
 */
class PropertyDocumentLines extends Model
{
    use Auditable;

    /** @use HasFactory<PropertyDocumentLinesFactory> */
    use HasFactory;

    protected $fillable = [
        'property_document_id',
        'issued_property_unit_id',
        'inventory_item_id',
        'quantity',
        'unit',
        'unit_cost',
        'item_name',
        'item_type',
        'brand',
        'model',
        'serial_number',
        'property_number',
        'estimated_useful_life_years',
        'date_acquired',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'estimated_useful_life_years' => 'integer',
            'date_acquired' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (self $line): void {
            $finalized = $line->relationLoaded('document')
                ? (bool) $line->document?->isFinalized()
                : PropertyDocuments::query()->whereKey($line->property_document_id)->whereNotNull('finalized_at')->exists();

            if ($finalized) {
                throw new LogicException('Lines of a finalized property document cannot be deleted.');
            }
        });
    }

    /**
     * The prefix an ICS property number starts with: LV for SPLV, HV for
     * SPHV; none for PAR. Pre-filled in the form, never stored on its own
     * (property_number is unique, so drafts keep it empty).
     */
    public static function propertyNumberPrefix(?PropertyClass $icsClass): string
    {
        return match ($icsClass) {
            PropertyClass::Splv => 'LV',
            PropertyClass::Sphv => 'HV',
            default => '',
        };
    }

    /**
     * @return Attribute<string, never>
     */
    protected function totalCost(): Attribute
    {
        return Attribute::get(fn (): string => Money::fromCentavos(
            Money::centavos((string) $this->unit_cost) * (int) $this->quantity,
        ));
    }

    /**
     * @return BelongsTo<PropertyDocuments, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(PropertyDocuments::class, 'property_document_id');
    }

    /**
     * @return BelongsTo<IssuedPropertyUnits, $this>
     */
    public function issuedPropertyUnit(): BelongsTo
    {
        return $this->belongsTo(IssuedPropertyUnits::class, 'issued_property_unit_id');
    }

    /**
     * @return BelongsTo<InventoryItems, $this>
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItems::class, 'inventory_item_id');
    }

    /**
     * The property tag queued for this line, if it is tagged.
     *
     * @return HasOne<PropertyTags, $this>
     */
    public function tag(): HasOne
    {
        return $this->hasOne(PropertyTags::class, 'property_document_line_id');
    }
}
