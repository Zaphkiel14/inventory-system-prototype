{{-- <x-chart.area field="received" label="Received" curve="smooth|linear" :gradient="true" :opacity="0.3" class="text-emerald-500" />
     With :gradient="false" the area is a solid fill of the class color (use e.g. text-emerald-500/20). --}}
@props([
'field' => 'value',
'label' => null,
'curve' => 'smooth',
'gradient' => true,
'opacity' => 0.3,
])

<div hidden data-chart-layer="area" data-options="{{ json_encode([
    'field' => $field,
    'label' => $label,
    'curve' => $curve,
    'gradient' => (bool) $gradient,
    'opacity' => (float) $opacity,
    'colored' => $attributes->has('class'),
]) }}" {{ $attributes->only('class') }}></div>
