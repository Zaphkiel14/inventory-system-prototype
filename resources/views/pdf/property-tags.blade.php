{{--
Property tag sheets: one page per sheet, one tag per slot, positioned in mm
by TagSheetLayout (calibration offsets already applied). $blank prints the
design without values.
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ ($blank ?? false) ? 'Blank property tags' : 'Property tags' }}</title>
<style>
    @page { size: {{ (float) $profile->page_width_mm }}mm {{ (float) $profile->page_height_mm }}mm; margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; }
    .sheet {
        position: relative;
        width: {{ (float) $profile->page_width_mm }}mm;
        height: {{ (float) $profile->page_height_mm }}mm;
        overflow: hidden;
    }
    .sheet.break { page-break-after: always; }
</style>
</head>
<body>
    @foreach ($sheets as $sheet)
        <div class="sheet{{ $loop->last ? '' : ' break' }}">
            @foreach ($sheet as $slot)
                @include('pdf.partials.property-tag', [
                    'x' => (float) $slot['x_mm'],
                    'y' => (float) $slot['y_mm'],
                    'width' => (float) $slot['width_mm'],
                    'height' => (float) $slot['height_mm'],
                    'tag' => ($blank ?? false) ? null : ($slot['content'] ?? null),
                    'blank' => (bool) ($blank ?? false),
                    'logo' => $logo ?? null,
                    'entity_name' => $entity_name,
                ])
            @endforeach
        </div>
    @endforeach
</body>
</html>
