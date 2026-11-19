{{-- <x-chart.tooltip field="date" :format="['dateStyle' => 'medium']" :value-format="['style' => 'currency', 'currency' => 'PHP']" />
     Heading defaults to the category axis label; values list every series. --}}
@props([
'field' => null,
'format' => null,
'valueFormat' => null,
])

<div hidden data-chart-layer="tooltip" data-options="{{ json_encode(array_filter([
    'field' => $field,
    'format' => $format,
    'valueFormat' => $valueFormat,
], fn ($v) => $v !== null)) }}"></div>
