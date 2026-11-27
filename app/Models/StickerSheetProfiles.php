<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\StickerSheetProfilesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sticker sheet layout for property tags, in millimetres. Exactly one
 * profile is the default: saving one as default clears the flag on the rest.
 * Offsets are printer calibration and may be negative.
 *
 * @property int $id
 * @property string $name
 * @property string $page_width_mm
 * @property string $page_height_mm
 * @property int $columns
 * @property int $rows
 * @property string $tag_width_mm
 * @property string $tag_height_mm
 * @property string $margin_top_mm
 * @property string $margin_left_mm
 * @property string $gap_x_mm
 * @property string $gap_y_mm
 * @property string $offset_x_mm
 * @property string $offset_y_mm
 * @property bool $is_default
 * @property CarbonImmutable $created_at
 */
class StickerSheetProfiles extends Model
{
    /** @use HasFactory<StickerSheetProfilesFactory> */
    use HasFactory;

    /** The layout every environment starts with (also inserted by its migration). */
    public const DEFAULT_PROFILE = [
        'name' => 'A4 · 2 × 5',
        'page_width_mm' => '210.00',
        'page_height_mm' => '297.00',
        'columns' => 2,
        'rows' => 5,
        'tag_width_mm' => '95.00',
        'tag_height_mm' => '54.00',
        'margin_top_mm' => '9.50',
        'margin_left_mm' => '7.50',
        'gap_x_mm' => '5.00',
        'gap_y_mm' => '2.00',
        'offset_x_mm' => '0.00',
        'offset_y_mm' => '0.00',
    ];

    protected $fillable = [
        'name',
        'page_width_mm',
        'page_height_mm',
        'columns',
        'rows',
        'tag_width_mm',
        'tag_height_mm',
        'margin_top_mm',
        'margin_left_mm',
        'gap_x_mm',
        'gap_y_mm',
        'offset_x_mm',
        'offset_y_mm',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_width_mm' => 'decimal:2',
            'page_height_mm' => 'decimal:2',
            'columns' => 'integer',
            'rows' => 'integer',
            'tag_width_mm' => 'decimal:2',
            'tag_height_mm' => 'decimal:2',
            'margin_top_mm' => 'decimal:2',
            'margin_left_mm' => 'decimal:2',
            'gap_x_mm' => 'decimal:2',
            'gap_y_mm' => 'decimal:2',
            'offset_x_mm' => 'decimal:2',
            'offset_y_mm' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Only one default: a query update, so it doesn't re-enter this hook.
        static::saved(function (self $profile): void {
            if ($profile->is_default && ($profile->wasRecentlyCreated || $profile->wasChanged('is_default'))) {
                self::query()->whereKeyNot($profile->id)->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    /**
     * The default profile, falling back to the oldest one; creates the
     * standard A4 2 × 5 profile when the table is empty.
     */
    public static function default(): self
    {
        return self::query()->orderByDesc('is_default')->orderBy('id')->first()
            ?? self::create([...self::DEFAULT_PROFILE, 'is_default' => true]);
    }

    /**
     * @return HasMany<PropertyTagPrints, $this>
     */
    public function prints(): HasMany
    {
        return $this->hasMany(PropertyTagPrints::class, 'sticker_sheet_profile_id');
    }
}
