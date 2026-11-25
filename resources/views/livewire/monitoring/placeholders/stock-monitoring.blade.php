{{-- Placeholder for the deferred StockMonitoring component. Mirrors livewire/monitoring/stock-monitoring.blade.php. --}}
<div class="flex flex-col gap-6">
    <span class="sr-only" role="status">Loading stock monitoring…</span>

    {{-- Header --}}
    <flux:card variant="default">
        <flux:heading size="xl" level="2">Stock Monitoring</flux:heading>
        <flux:text class="mt-1">
            Usable, committed and free stock per item against its thresholds. Expand an item to see its batches in issue order.
        </flux:text>
    </flux:card>

    {{-- Status tiles --}}
    <x-skeleton.stat-tiles :count="count(\App\Enums\StockStatus::cases())"
        :labels="array_map(fn ($case) => $case->label(), \App\Enums\StockStatus::cases())" />

    {{-- Item list --}}
    <flux:card>
        <flux:skeleton.group animate="shimmer">
            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center" aria-hidden="true">
                <div class="lg:flex-1">
                    <flux:skeleton class="h-8 w-full rounded-lg" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-48" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-48" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-48" />
                </div>
            </div>

            <x-skeleton.table-rows :rows="8" :columns="9" />
        </flux:skeleton.group>
    </flux:card>
</div>
