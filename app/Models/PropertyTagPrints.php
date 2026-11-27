<?php

namespace App\Models;

use App\Enums\TagPrintKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Append-only print log. A Reprint carries a reason (enforced by the print
 * service); Blank and Calibration sheets have no tag.
 *
 * @property int $id
 * @property int|null $property_tag_id
 * @property int|null $user_id
 * @property TagPrintKind $kind
 * @property string|null $reason
 * @property int $sticker_sheet_profile_id
 * @property CarbonImmutable $printed_at
 */
class PropertyTagPrints extends Model
{
    protected $fillable = [
        'property_tag_id',
        'user_id',
        'kind',
        'reason',
        'sticker_sheet_profile_id',
        'printed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => TagPrintKind::class,
            'printed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Tag print records are permanent and cannot be modified.'));
        static::deleting(fn () => throw new LogicException('Tag print records are permanent and cannot be deleted.'));
    }

    /**
     * @return BelongsTo<PropertyTags, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(PropertyTags::class, 'property_tag_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<StickerSheetProfiles, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(StickerSheetProfiles::class, 'sticker_sheet_profile_id');
    }
}
