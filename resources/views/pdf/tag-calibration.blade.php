{{--
Calibration page for a sticker sheet profile: every slot as a thin outline
with its number and a centre crosshair, a 10 mm ruler at the top-left page
corner, and a caption with the profile's measurements. Print it on plain
paper, lay it over a sticker sheet and adjust the X/Y offsets until the
outlines match the stickers.
--}}
@php
    $mm = fn ($value): string => number_format((float) $value, 2);
    $firstTop = collect($slots)->min('y_mm') ?? 0;
    // The caption sits in the top margin when there's room above the first row, otherwise at the bottom.
    $captionInTop = $firstTop >= 9;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Calibration · {{ $profile->name }}</title>
<style>
    @page { size: {{ (float) $page_width_mm }}mm {{ (float) $page_height_mm }}mm; margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #000; }
    .page { position: relative; width: {{ (float) $page_width_mm }}mm; height: {{ (float) $page_height_mm }}mm; overflow: hidden; }
    .slot { position: absolute; border: 0.2mm solid #555; }
    .index { position: absolute; left: 1.2mm; top: 0.8mm; font-size: 7pt; color: #555; }
    .h, .v { position: absolute; background: #000; }
    .ruler { position: absolute; background: #000; }
    .caption { position: absolute; left: 0; width: {{ (float) $page_width_mm }}mm; text-align: center; font-size: 6.5pt; line-height: 1.3; }
    .caption strong { font-size: 7.5pt; }
</style>
</head>
<body>
    <div class="page">
        {{-- 10 mm ruler at the top-left corner --}}
        <div class="ruler" style="left: 2mm; top: 2mm; width: 10mm; height: 0.3mm;"></div>
        <div class="ruler" style="left: 2mm; top: 2mm; width: 0.3mm; height: 10mm;"></div>
        <div class="ruler" style="left: 11.7mm; top: 1mm; width: 0.3mm; height: 2.3mm;"></div>
        <div class="ruler" style="left: 1mm; top: 11.7mm; width: 2.3mm; height: 0.3mm;"></div>
        <div style="position: absolute; left: 3mm; top: 3mm; font-size: 5pt;">10 mm</div>

        @foreach ($slots as $slot)
            @php
                $cx = (float) $slot['x_mm'] + (float) $slot['width_mm'] / 2;
                $cy = (float) $slot['y_mm'] + (float) $slot['height_mm'] / 2;
            @endphp
            <div class="slot" style="left: {{ round((float) $slot['x_mm'], 3) }}mm; top: {{ round((float) $slot['y_mm'], 3) }}mm; width: {{ round((float) $slot['width_mm'], 3) }}mm; height: {{ round((float) $slot['height_mm'], 3) }}mm;">
                <div class="index">{{ $slot['index'] }}</div>
            </div>
            <div class="h" style="left: {{ round($cx - 4, 3) }}mm; top: {{ round($cy - 0.1, 3) }}mm; width: 8mm; height: 0.2mm;"></div>
            <div class="v" style="left: {{ round($cx - 0.1, 3) }}mm; top: {{ round($cy - 4, 3) }}mm; width: 0.2mm; height: 8mm;"></div>
        @endforeach

        <div class="caption" style="{{ $captionInTop ? 'top: 1.5mm;' : 'bottom: 1.5mm;' }}">
            <strong>{{ $profile->name }}</strong> · Print at 100% / Actual size, no fit-to-page<br>
            Page {{ $mm($page_width_mm) }} × {{ $mm($page_height_mm) }} mm ·
            {{ $profile->columns }} × {{ $profile->rows }} tags of {{ $mm($profile->tag_width_mm) }} × {{ $mm($profile->tag_height_mm) }} mm ·
            Margins top {{ $mm($profile->margin_top_mm) }}, left {{ $mm($profile->margin_left_mm) }} ·
            Gaps {{ $mm($profile->gap_x_mm) }} × {{ $mm($profile->gap_y_mm) }} ·
            Offset X {{ $mm($profile->offset_x_mm) }}, Y {{ $mm($profile->offset_y_mm) }} mm
        </div>
    </div>
</body>
</html>
