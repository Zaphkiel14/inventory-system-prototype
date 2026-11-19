{{-- <x-chart.pie field="qty" label-field="name" :colors="['text-emerald-500', 'text-sky-500']" />

     inner:  hole size as a fraction of the radius (0 = pie, .6 = donut, .82 = ring).
     gap:    px between slices.   format: Intl.NumberFormat options for values.
     max:    treat values as progress out of this total (unfilled part shows the track).
     track:  draw the background circle.
     The slot is centered in the hole, e.g. a total for donuts and rings. --}}
@props([
'field' => 'value',
'labelField' => 'label',
'inner' => 0,
'gap' => null,
'colors' => [],
'format' => null,
'max' => null,
'track' => false,
])

<div hidden data-chart-layer="pie" data-options="{{ json_encode(array_filter([
    'field' => $field,
    'labelField' => $labelField,
    'inner' => (float) $inner,
    'gap' => $gap === null ? null : (float) $gap,
    'format' => $format,
    'max' => $max === null ? null : (float) $max,
    'track' => (bool) $track,
], fn ($v) => $v !== null)) }}">
    @foreach ($colors as $color)
    <span data-swatch class="{{ $color }}"></span>
    @endforeach
</div>

@if ($slot->isNotEmpty())
<div {{ $attributes->class('fc-center') }}>{{ $slot }}</div>
@endif
