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

@once
<style>
    .fc {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 0;
        --fc-tick: #a1a1aa;
        --fc-grid: #f4f4f5;
        --fc-axis: #e4e4e7;
        --fc-cursor: #d4d4d8;
        --fc-band: rgb(0 0 0 / .04);
        --fc-track: #f4f4f5;
        --fc-dot-ring: #fff;
    }

    .dark .fc {
        --fc-tick: #a1a1aa;
        --fc-grid: rgb(255 255 255 / .08);
        --fc-axis: rgb(255 255 255 / .15);
        --fc-cursor: rgb(255 255 255 / .25);
        --fc-band: rgb(255 255 255 / .06);
        --fc-track: rgb(255 255 255 / .08);
        --fc-dot-ring: #262626;
    }

    .fc-plot {
        position: relative;
        flex: 1 1 auto;
        min-height: 0;
    }

    .fc-svg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        overflow: visible;
    }

    .fc-overlay {
        pointer-events: none;
    }

    .fc-overlay.is-under {
        z-index: 0;
    }

    .fc-tick {
        fill: var(--fc-tick);
        font-size: 12px;
        font-variant-numeric: tabular-nums;
    }

    .fc-grid {
        stroke: var(--fc-grid);
        stroke-width: 1;
    }

    .fc-axis {
        stroke: var(--fc-axis);
        stroke-width: 1;
    }

    .fc-cursor {
        stroke: var(--fc-cursor);
        stroke-width: 1;
        stroke-dasharray: 3 3;
    }

    .fc-band {
        fill: var(--fc-band);
    }

    .fc-track {
        fill: var(--fc-track);
    }

    .fc-dot {
        stroke: var(--fc-dot-ring);
        stroke-width: 2;
    }

    .fc-svg path[data-slice] {
        transition: opacity .15s;
    }

    .fc-center {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        pointer-events: none;
    }

    /* ---- tooltip ---- */
    .fc-tooltip {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 20;
        min-width: 120px;
        padding: 8px 10px;
        border: 1px solid #e4e4e7;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -2px rgb(0 0 0 / .1);
        font-size: 13px;
        line-height: 1.3;
        color: #3f3f46;
        white-space: nowrap;
        pointer-events: none;
    }

    .dark .fc-tooltip {
        border-color: var(--color-zinc-600, #525252);
        background: var(--color-zinc-700, #404040);
        color: #f4f4f5;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / .4);
    }

    .fc-tip-heading {
        margin-bottom: 6px;
        font-weight: 500;
        color: #71717a;
        font-size: 12px;
    }

    .dark .fc-tip-heading,
    .fc-tip-muted {
        color: #a1a1aa;
    }

    .fc-tip-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fc-tip-row+.fc-tip-row {
        margin-top: 4px;
    }

    .fc-tip-dot {
        flex: none;
        width: 8px;
        height: 8px;
        border-radius: 2px;
    }

    .fc-tip-label {
        flex: 1;
    }

    .fc-tip-value {
        margin-left: 12px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    /* ---- legend ---- */
    .fc-legend {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 6px 16px;
        font-size: 13px;
        color: #52525b;
    }

    .dark .fc-legend {
        color: #d4d4d8;
    }

    .fc-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .fc-legend-value {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
</style>
@endonce

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
