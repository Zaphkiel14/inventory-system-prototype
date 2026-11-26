{{-- Drill-down for one Stock Monitoring row, lazy-loaded the first time that row is opened. --}}
<div>
    @if ($detail = $this->detail)
        @include('livewire.monitoring.partials.stock-detail', ['detail' => $detail])
    @else
        <div class="p-4">
            <x-reports.empty icon="cube" message="This item is no longer available." class="h-24" />
        </div>
    @endif
</div>
