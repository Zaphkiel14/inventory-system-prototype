{{-- <x-chart.line field="received" label="Received" curve="smooth|linear" :width="2" dashed class="text-emerald-500" /> --}}
@props([
'field' => 'value',
'label' => null,
'curve' => 'smooth',
'width' => 2,
'dashed' => false,
])

<div hidden data-chart-layer="line" data-options="{{ json_encode([
    'field' => $field,
    'label' => $label,
    'curve' => $curve,
    'width' => (float) $width,
    'dashed' => (bool) $dashed,
    'colored' => $attributes->has('class'),
]) }}" {{ $attributes->only('class') }}></div>
