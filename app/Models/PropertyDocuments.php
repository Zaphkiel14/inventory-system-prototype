<?php

namespace App\Models;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Database\Factories\PropertyDocumentsFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * An ICS (semi-expendables, split SPLV/SPHV by ics_class) or a PAR (PPE).
 * Custody records only: they never move stock. A finalized document can't
 * be deleted.
 *
 * "from" is Received from (ICS) / Issued by (PAR); "to" is Received by, the
 * accountable person.
 *
 * @property int $id
 * @property PropertyDocumentType $type
 * @property PropertyClass|null $ics_class
 * @property string|null $document_number
 * @property string|null $fund_cluster
 * @property CarbonImmutable $document_date
 * @property int|null $requisition_id
 * @property int|null $stock_issuance_id
 * @property string|null $iar_number
 * @property string|null $from_name
 * @property string|null $from_position
 * @property CarbonImmutable|null $from_date
 * @property string|null $to_name
 * @property string|null $to_position
 * @property CarbonImmutable|null $to_date
 * @property PropertyDocumentStatus $status
 * @property CarbonImmutable|null $finalized_at
 * @property CarbonImmutable|null $renewal_due_on
 */
class PropertyDocuments extends Model
{
    use Auditable;

    /** @use HasFactory<PropertyDocumentsFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'ics_class',
        'document_number',
        'fund_cluster',
        'document_date',
        'requisition_id',
        'stock_issuance_id',
        'iar_number',
        'from_name',
        'from_position',
        'from_date',
        'to_name',
        'to_position',
        'to_date',
        'status',
        'finalized_at',
        'renewal_due_on',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PropertyDocumentType::class,
            'ics_class' => PropertyClass::class,
            'status' => PropertyDocumentStatus::class,
            'document_date' => 'date',
            'from_date' => 'date',
            'to_date' => 'date',
            'finalized_at' => 'datetime',
            'renewal_due_on' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // An ICS is either SPLV or SPHV; a PAR has no class.
        static::saving(function (self $document): void {
            $valid = $document->type === PropertyDocumentType::Ics
                ? in_array($document->ics_class, [PropertyClass::Splv, PropertyClass::Sphv], true)
                : $document->ics_class === null;

            if (! $valid) {
                throw new LogicException('An ICS needs an SPLV or SPHV class, and a PAR has none.');
            }
        });

        static::deleting(function (self $document): void {
            if ($document->isFinalized()) {
                throw new LogicException('Finalized property documents cannot be deleted.');
            }
        });
    }

    /**
     * e.g. "ICS SPLV-2026-10-062", "PAR 2026-01-003", or "ICS draft #4".
     */
    public function label(): string
    {
        return strtoupper($this->type->value).' '.($this->document_number ?: "draft #{$this->id}");
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDrafts(Builder $query): void
    {
        $query->where('status', PropertyDocumentStatus::Draft);
    }

    /**
     * @return HasMany<PropertyDocumentLines, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PropertyDocumentLines::class, 'property_document_id');
    }

    /**
     * @return BelongsTo<Requisitions, $this>
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    /**
     * @return BelongsTo<StockIssuances, $this>
     */
    public function stockIssuance(): BelongsTo
    {
        return $this->belongsTo(StockIssuances::class, 'stock_issuance_id');
    }
}
