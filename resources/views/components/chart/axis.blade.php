{{-- <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
     <x-chart.axis axis="y" :format="['notation' => 'compact']" :grid="true" :line="false" :ticks="true" :min="0" :max="100" />

     field:  the category field (x for vertical charts, y for horizontal bars).
     format: Intl.DateTimeFormat options (dates) or Intl.NumberFormat options (numbers).
     grid:   gridlines; on by default for the value axis, off for the category axis. --}}
@props([
'axis' => 'x',
'field' => null,
'format' => null,
'grid' => null,
'line' => null,
'ticks' => true,
'min' => null,
'max' => null,
])

<div hidden data-chart-layer="axis" data-options="{{ json_encode(array_filter([
    'axis' => $axis,
    'field' => $field,
    'format' => $format,
    'grid' => $grid,
    'line' => $line,
    'ticks' => (bool) $ticks,
    'min' => $min,
    'max' => $max,
], fn ($v) => $v !== null)) }}"></div>
