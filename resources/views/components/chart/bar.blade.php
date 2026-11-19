{{-- <x-chart.bar field="received" label="Received" :radius="4" class="text-emerald-500" />
     Several bars are grouped side by side; add `stacked` / `horizontal` on <x-chart>. --}}
@props([
'field' => 'value',
'label' => null,
'radius' => 4,
])

<div hidden data-chart-layer="bar" data-options="{{ json_encode([
    'field' => $field,
    'label' => $label,
    'radius' => (float) $radius,
    'colored' => $attributes->has('class'),
]) }}" {{ $attributes->only('class') }}></div>
