<?php

namespace App\Models;

use App\Enums\PropertyTagStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PropertyTagsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The property tag for one ICS/PAR line. What it prints is read live from
 * the line and its document (PropertyTagContent); this row only tracks
 * printing.
 *
 * @property int $id
 * @property int $property_document_line_id
 * @property PropertyTagStatus $status
 * @property int $print_count
 * @property CarbonImmutable|null $last_printed_at
 * @property int|null $last_printed_by
 */
class PropertyTags extends Model
{
    /** @use HasFactory<PropertyTagsFactory> */
    use HasFactory;

    protected $fillable = [
        'property_document_line_id',
        'status',
        'print_count',
        'last_printed_at',
        'last_printed_by',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'print_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PropertyTagStatus::class,
            'print_count' => 'integer',
            'last_printed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PropertyDocumentLines, $this>
     */
    public function line(): BelongsTo
    {
        return $this->belongsTo(PropertyDocumentLines::class, 'property_document_line_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastPrintedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_printed_by');
    }

    /**
     * @return HasMany<PropertyTagPrints, $this>
     */
    public function prints(): HasMany
    {
        return $this->hasMany(PropertyTagPrints::class, 'property_tag_id');
    }
}
