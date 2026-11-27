<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\StickerSheetProfiles;
use App\Services\Property\PropertyTagSheet;
use App\Services\Property\TagSheetLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

/**
 * Sticker PDFs, streamed inline: a sheet of selected tags, blank stickers, or
 * the calibration page. Previews only: these routes never record a print
 * (PropertyTagQueue logs prints when the user confirms them). The routes are
 * signed, so the queue hands out short-lived links.
 */
class PropertyTagPdfController extends Controller
{
    /** Minutes a preview link stays valid. */
    public const LINK_MINUTES = 30;

    public const MAX_BLANK = 200;

    public function __invoke(Request $request): Response
    {
        $profile = StickerSheetProfiles::query()->findOrFail((int) $request->query('profile'));

        return match ($request->route()?->getName()) {
            'property-management.tags.sheet' => $this->sheet($request, $profile),
            'property-management.tags.blank' => $this->blank($request, $profile),
            default => $this->calibration($profile),
        };
    }

    /**
     * @param  array<int, int>  $tagIds
     */
    public static function sheetUrl(StickerSheetProfiles $profile, array $tagIds, int $startSlot): string
    {
        return URL::temporarySignedRoute('property-management.tags.sheet', now()->addMinutes(self::LINK_MINUTES), [
            'tags' => implode(',', $tagIds),
            'profile' => $profile->id,
            'start' => $startSlot,
        ]);
    }

    public static function blankUrl(StickerSheetProfiles $profile, int $count, int $startSlot): string
    {
        return URL::temporarySignedRoute('property-management.tags.blank', now()->addMinutes(self::LINK_MINUTES), [
            'count' => $count,
            'profile' => $profile->id,
            'start' => $startSlot,
        ]);
    }

    public static function calibrationUrl(StickerSheetProfiles $profile): string
    {
        return URL::temporarySignedRoute('property-management.tags.calibration', now()->addMinutes(self::LINK_MINUTES), [
            'profile' => $profile->id,
        ]);
    }

    private function sheet(Request $request, StickerSheetProfiles $profile): Response
    {
        $ids = collect(explode(',', (string) $request->query('tags')))
            ->map(fn (string $id) => (int) trim($id))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        abort_if($ids === [], 404);

        $data = PropertyTagSheet::forTags($profile, $ids, $this->startSlot($request, $profile));

        return $this->stream('pdf.property-tags', $data, $profile, 'property-tags');
    }

    private function blank(Request $request, StickerSheetProfiles $profile): Response
    {
        $count = (int) $request->query('count');

        abort_unless($count >= 1 && $count <= self::MAX_BLANK, 422, 'Blank sticker count must be between 1 and '.self::MAX_BLANK.'.');

        $data = PropertyTagSheet::blank($profile, $count, $this->startSlot($request, $profile));

        return $this->stream('pdf.property-tags', $data, $profile, 'blank-stickers');
    }

    private function calibration(StickerSheetProfiles $profile): Response
    {
        return $this->stream('pdf.tag-calibration', PropertyTagSheet::calibration($profile), $profile, 'tag-calibration');
    }

    private function startSlot(Request $request, StickerSheetProfiles $profile): int
    {
        $start = (int) $request->query('start', '1');

        abort_unless($start >= 1 && $start <= TagSheetLayout::slotsPerSheet($profile), 422, 'The starting slot is outside the sheet.');

        return $start;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function stream(string $view, array $data, StickerSheetProfiles $profile, string $name): Response
    {
        return Pdf::loadView($view, $data)
            ->setPaper(PropertyTagSheet::paper($profile))
            ->stream($name.'-'.now()->format('Ymd-His').'.pdf');
    }
}
