{{-- <x-chart.legend /> — series (or slice) labels below the chart. `values` also shows slice values. --}}
@props(['values' => false])

<div hidden data-chart-layer="legend" data-options="{{ json_encode(['values' => (bool) $values]) }}"></div>
