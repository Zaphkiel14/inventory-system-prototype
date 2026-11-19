{{-- <x-chart.donut field="qty" label-field="name">center content</x-chart.donut> — a pie with a 0.6 hole. See pie.blade.php for props. --}}
@props([
'field' => 'value',
'labelField' => 'label',
'inner' => 0.6,
'gap' => null,
'colors' => [],
'format' => null,
'max' => null,
'track' => false,
])

<x-chart.pie :field="$field" :label-field="$labelField" :inner="$inner" :gap="$gap" :colors="$colors"
    :format="$format" :max="$max" :track="$track" {{ $attributes }}>{{ $slot }}</x-chart.pie>
