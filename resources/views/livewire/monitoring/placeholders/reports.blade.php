{{-- Placeholder for the deferred Reports component. Mirrors livewire/monitoring/reports.blade.php. --}}
<div class="flex flex-col gap-6">
    <span class="sr-only" role="status">Loading reports…</span>

    {{-- Header + period filter --}}
    <flux:card variant="default">
        <flux:skeleton.group animate="pulse" class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Reports</flux:heading>
                <div class="mt-1 flex flex-wrap items-center gap-x-1">
                    <flux:text>Stock levels, movement and activity for</flux:text>
                    <flux:skeleton.line class="w-48" aria-hidden="true" />
                </div>
            </div>

            <flux:skeleton class="h-8 w-full rounded-lg sm:w-44" aria-hidden="true" />
        </flux:skeleton.group>
    </flux:card>

    <div class="flex flex-col gap-6">

        {{-- Summary tiles --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach (['Stock value on hand', 'Units received', 'Units issued', 'Net adjustments', 'Items needing reorder', 'Expiring in 30 days'] as $label)
                <flux:card size="sm" class="flex flex-col gap-2">
                    <flux:skeleton.group animate="pulse" class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:skeleton class="size-4 rounded" aria-hidden="true" />
                        </div>
                        <flux:skeleton class="h-8 w-20" aria-hidden="true" />
                        <flux:skeleton.line class="w-32" aria-hidden="true" />
                    </flux:skeleton.group>
                </flux:card>
            @endforeach
        </div>

        {{-- Movement + stock health --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-skeleton.chart class="lg:col-span-2" height="h-72" heading="Stock movement" :action="false" />
            <x-skeleton.chart height="h-72" heading="Stock health" description="Active items by usable stock vs. thresholds"
                :action="false" donut />
        </div>

        {{-- Receiving + value by location --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <x-skeleton.chart height="h-60" heading="Receiving by supplier" description="Value of stock received in this period"
                :action="false" />
            <x-skeleton.chart height="h-60" heading="Stock value by location" description="Usable stock currently on hand"
                :action="false" />
        </div>

        {{-- Issued items + adjustments + requisitions --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <x-skeleton.chart height="h-64" heading="Top issued items" description="Most units released in this period"
                :action="false" />
            <x-skeleton.chart height="h-64" heading="Adjustments by type" description="Number of adjustments recorded"
                :action="false" donut />
            <x-skeleton.chart height="h-64" heading="Requisitions by status" description="Requests submitted in this period"
                :action="false" donut />
        </div>

        {{-- Reorder list --}}
        <x-skeleton.table :rows="5" :columns="7" heading="Items needing reorder"
            description="Active items at or below their reorder level (current stock, not limited to the period)" />
    </div>
</div>
