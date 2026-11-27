{{--
One property tag (Property Tag, Rev 0 · 21 November 2022), drawn inside a fixed
box positioned absolutely in mm, so labels and values always print together.

Expects:
  $x, $y, $width, $height   position and size in mm (calibration offsets already applied)
  $tag                      PropertyTagContent::for() content, or null/[] for a blank sticker
  $blank                    true prints the design without values (blank stickers)
  $logo                     data URI or null
  $entity_name              hospital name

Value sizes and line breaks come from App\Services\Property\TagTextFitter:
each value gets the largest size that fits on one line, else exactly two
lines, else is cut with "…". Each line is its own nowrap div, so dompdf
never adds a third line and nothing leaves its row.
--}}
@use('App\Services\Property\TagTextFitter', 'Fitter')
@php
    $blank = (bool) ($blank ?? false);
    $tag = $tag ?? [];

    $scale = Fitter::scaleFor($width, $height);
    $rowHeight = Fitter::rowHeightFor($height, $scale);
    $tableWidth = $width - 3 * $scale;
    $pt = fn (float $size): string => round($size * $scale, 2).'pt';
    $mm = fn (float $size): string => round($size * $scale, 3).'mm';
    $lineMm = fn (float $sizePt): float => $sizePt * Fitter::RENDERED_LINE_EM * Fitter::PT_TO_MM;

    $dateCost = collect([
        ($tag['date_acquired'] ?? null) instanceof \DateTimeInterface ? $tag['date_acquired']->format('m/d/Y') : null,
        filled($tag['cost'] ?? null) ? '₱'.number_format((float) $tag['cost'], 2) : null,
    ])->filter()->implode(' / ');

    // [label, value, monospace]
    $rows = [
        ['Property No.:', $tag['property_number'] ?? null, true],
        ['Item:', $tag['item'] ?? null, false],
        ['Model:', $tag['model'] ?? null, false],
        ['Serial No.:', $tag['serial_number'] ?? null, true],
        ['Date Acquired/Cost:', $dateCost, false],
        ['Accountable Person:', $tag['accountable_person'] ?? null, false],
        ['Inventory Committee Rep.:', null, false],
    ];
@endphp
<div style="position: absolute; left: {{ round($x, 3) }}mm; top: {{ round($y, 3) }}mm; width: {{ round($width, 3) }}mm; height: {{ round($height, 3) }}mm; overflow: hidden; border: 0.3mm solid #000; font-family: 'DejaVu Sans', sans-serif; color: #000;">
    {{-- Header --}}
    <table style="width: 100%; border-collapse: collapse; height: {{ $mm(15) }};">
        <tr>
            <td style="width: {{ $mm(13) }}; vertical-align: middle; padding: {{ $mm(1) }} 0 0 {{ $mm(1.5) }};">
                @if (filled($logo ?? null))
                    <img src="{{ $logo }}" alt="" style="height: {{ $mm(11) }}; width: auto;">
                @endif
            </td>
            <td style="vertical-align: middle; text-align: center; padding-top: {{ $mm(1) }}; line-height: 1.15;">
                <div style="font-size: {{ $pt(4.6) }};">Republic of the Philippines</div>
                <div style="font-size: {{ $pt(4.6) }};">Department of Health · Eastern Visayas</div>
                <div style="font-size: {{ $pt(5.6) }}; font-weight: bold; text-transform: uppercase;">{{ $entity_name }}</div>
            </td>
            <td style="width: {{ $mm(20) }}; vertical-align: middle; padding: {{ $mm(1) }} {{ $mm(1.5) }} 0 0;">
                <div style="border: 0.25mm solid #000; text-align: center; padding: {{ $mm(0.6) }} {{ $mm(0.4) }}; line-height: 1.1;">
                    <div style="font-size: {{ $pt(6) }}; font-weight: bold;">PROPERTY TAG</div>
                    <div style="font-size: {{ $pt(3.6) }};">Rev 0 · 21 November 2022</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Label / value rows: one table so each label stays beside its value --}}
    <table style="width: {{ round($tableWidth, 3) }}mm; margin: {{ $mm(1) }} {{ $mm(1.5) }} 0 {{ $mm(1.5) }}; border-collapse: collapse; table-layout: fixed;">
        @foreach ($rows as [$label, $value, $mono])
            @php
                $fitted = $blank ? ['size_pt' => Fitter::MAX_PT * $scale, 'lines' => []] : Fitter::fitForTag($value, $width, $height);
                $padTop = max(0, $rowHeight - max(1, count($fitted['lines'])) * $lineMm($fitted['size_pt']) - 0.4 * $scale);
            @endphp
            <tr>
                <td style="width: {{ Fitter::LABEL_PERCENT }}%; height: {{ round($rowHeight, 3) }}mm; padding: 0; vertical-align: bottom; font-size: {{ $pt(5) }}; font-weight: bold; white-space: nowrap;">
                    {{ $label }}
                </td>
                <td style="width: {{ 100 - Fitter::LABEL_PERCENT }}%; height: {{ round($rowHeight, 3) }}mm; padding: 0 0 0 {{ $mm(1) }}; vertical-align: top; border-bottom: 0.2mm solid #000;">
                    <div style="height: {{ round($rowHeight, 3) }}mm; overflow: hidden;">
                        <div style="padding-top: {{ round($padTop, 3) }}mm; font-size: {{ round($fitted['size_pt'], 2) }}pt; line-height: 1.1;{{ $mono ? " font-family: 'DejaVu Sans Mono', monospace;" : '' }}">
                            @foreach ($fitted['lines'] as $line)
                                <div style="white-space: nowrap; overflow: hidden;">{{ $line }}</div>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </table>
</div>
