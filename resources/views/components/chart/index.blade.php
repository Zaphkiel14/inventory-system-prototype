{{--
resources/views/components/chart/index.blade.php

Reusable charts (free alternative to Flux Pro's <flux:chart>), drawn as SVG by
resources/js/chart.js. Compose a chart from child components, as in Flux:

<x-chart :value="$data" class="aspect-3/1">
    <x-chart.line field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
    <x-chart.area field="received" class="text-emerald-500" />
    <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
    <x-chart.axis axis="y" />
    <x-chart.cursor />
    <x-chart.tooltip />
    <x-chart.legend />
</x-chart>

Data (:value or wire:model): a list of rows, a list of plain numbers (field "value"),
or a [label => value] map. Series colors come from each child's `text-*` class
(dark: variants work); without one they use the default palette below.

Props: value, stacked (bars / areas), horizontal (bars), label (accessible name).
Series:  line, area, point, bar          Pie family: pie, donut, ring
Parts:   axis, cursor, tooltip, legend   Sparkline: a chart with no axis/tooltip.

Styles (.fc-*) live in resources/css/app.css.
--}}
@props([
'value' => [],
'stacked' => false,
'horizontal' => false,
'label' => null,
])

@php
$model = $attributes->wire('model');
$name = $model->value();
@endphp

<div {{ $attributes->whereDoesntStartWith('wire:model')->class('fc') }} x-data="fluxChart(@js([
        'model' => $name,
        'stacked' => (bool) $stacked,
        'horizontal' => (bool) $horizontal,
    ]))" data-value="{{ json_encode($name ? [] : $value) }}" role="img" @if ($label) aria-label="{{ $label }}" @endif>
    {{-- Default series palette (read via computed color, so dark: variants apply). --}}
    <div data-chart-palette hidden>
        <span class="text-emerald-500 dark:text-emerald-400"></span>
        <span class="text-sky-500 dark:text-sky-400"></span>
        <span class="text-amber-500 dark:text-amber-400"></span>
        <span class="text-violet-500 dark:text-violet-400"></span>
        <span class="text-rose-500 dark:text-rose-400"></span>
        <span class="text-teal-500 dark:text-teal-400"></span>
        <span class="text-orange-500 dark:text-orange-400"></span>
        <span class="text-indigo-500 dark:text-indigo-400"></span>
        <span class="text-lime-500 dark:text-lime-400"></span>
        <span class="text-pink-500 dark:text-pink-400"></span>
    </div>

    <div class="fc-plot" x-ref="plot" x-on:pointermove="onMove($event)" x-on:pointerleave="onLeave()">
        <svg wire:ignore x-ref="overlayUnder" class="fc-svg fc-overlay" aria-hidden="true"></svg>
        <svg wire:ignore x-ref="svg" class="fc-svg" aria-hidden="true"></svg>
        <svg wire:ignore x-ref="overlay" class="fc-svg fc-overlay" aria-hidden="true"></svg>

        <div x-ref="slot">{{ $slot }}</div>

        <div wire:ignore x-ref="tooltip" class="fc-tooltip" style="display: none"></div>
    </div>

    <div wire:ignore x-ref="legend" class="fc-legend" style="display: none"></div>
</div>
