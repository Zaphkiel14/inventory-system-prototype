{{-- <x-chart.point field="received" label="Received" :size="4" class="text-emerald-500" />
     On its own it makes a points (dot) chart; next to a line it marks each value. --}}
@props([
'field' => 'value',
'label' => null,
'size' => 4,
])

<div hidden data-chart-layer="point" data-options="{{ json_encode([
    'field' => $field,
    'label' => $label,
    'size' => (float) $size,
    'colored' => $attributes->has('class'),
]) }}" {{ $attributes->only('class') }}></div>
