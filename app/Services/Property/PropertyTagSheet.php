<?php

namespace App\Services\Property;

use App\Models\PropertyTags;
use App\Models\StickerSheetProfiles;
use App\Support\DocumentLogo;
use App\Support\PropertySettings;
use Illuminate\Database\Eloquent\Collection;

/**
 * View data for the sticker PDFs (pdf.property-tags, pdf.tag-calibration):
 * tags placed on sheets by TagSheetLayout, each slot carrying its tag's
 * content. Read-only: building a sheet never records a print.
 */
class PropertyTagSheet
{
    /**
     * Tags in print order: document date, then document, then line.
     *
     * @param  array<int, int>  $tagIds
     * @return Collection<int, PropertyTags>
     */
    public static function orderedTags(array $tagIds): Collection
    {
        return PropertyTags::query()
            ->select('property_tags.*')
            ->join('property_document_lines', 'property_document_lines.id', '=', 'property_tags.property_document_line_id')
            ->join('property_documents', 'property_documents.id', '=', 'property_document_lines.property_document_id')
            ->whereIn('property_tags.id', $tagIds)
            ->orderBy('property_documents.document_date')
            ->orderBy('property_documents.id')
            ->orderBy('property_document_lines.id')
            ->with('line.document')
            ->get();
    }

    /**
     * @param  array<int, int>  $tagIds
     * @return array{profile: StickerSheetProfiles, sheets: list<list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float, content: array<string, mixed>|null}>>, blank: bool, logo: ?string, entity_name: string}
     */
    public static function forTags(StickerSheetProfiles $profile, array $tagIds, int $startSlot = 1): array
    {
        $contents = array_values(self::orderedTags($tagIds)
            ->map(fn (PropertyTags $tag) => PropertyTagContent::for($tag))
            ->all());

        return self::build($profile, $contents, $startSlot, blank: false);
    }

    /**
     * @return array{profile: StickerSheetProfiles, sheets: list<list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float, content: array<string, mixed>|null}>>, blank: bool, logo: ?string, entity_name: string}
     */
    public static function blank(StickerSheetProfiles $profile, int $count, int $startSlot = 1): array
    {
        return self::build($profile, array_fill(0, $count, null), $startSlot, blank: true);
    }

    /**
     * @return array{profile: StickerSheetProfiles, slots: list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}>, page_width_mm: float, page_height_mm: float}
     */
    public static function calibration(StickerSheetProfiles $profile): array
    {
        return [
            'profile' => $profile,
            'slots' => TagSheetLayout::calibration($profile),
            'page_width_mm' => (float) $profile->page_width_mm,
            'page_height_mm' => (float) $profile->page_height_mm,
        ];
    }

    /**
     * Page size in points for dompdf's setPaper().
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    public static function paper(StickerSheetProfiles $profile): array
    {
        return [0.0, 0.0, (float) $profile->page_width_mm * 72 / 25.4, (float) $profile->page_height_mm * 72 / 25.4];
    }

    /**
     * @param  list<array<string, mixed>|null>  $contents
     * @return array{profile: StickerSheetProfiles, sheets: list<list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float, content: array<string, mixed>|null}>>, blank: bool, logo: ?string, entity_name: string}
     */
    private static function build(StickerSheetProfiles $profile, array $contents, int $startSlot, bool $blank): array
    {
        $next = 0;
        $sheets = [];

        foreach (TagSheetLayout::place($profile, count($contents), $startSlot) as $sheet) {
            $sheets[] = array_map(function (array $slot) use ($contents, &$next): array {
                return [...$slot, 'content' => $contents[$next++] ?? null];
            }, $sheet);
        }

        return [
            'profile' => $profile,
            'sheets' => $sheets,
            'blank' => $blank,
            'logo' => DocumentLogo::dataUri(),
            'entity_name' => PropertySettings::entityName(),
        ];
    }
}
