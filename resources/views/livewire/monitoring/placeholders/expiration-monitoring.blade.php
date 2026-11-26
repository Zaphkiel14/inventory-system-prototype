{{-- Placeholder for the deferred ExpirationMonitoring component. Mirrors livewire/monitoring/expiration-monitoring.blade.php. --}}
<div class="flex flex-col gap-6">
    <span class="sr-only" role="status">Loading expiration monitoring…</span>

    {{-- Header --}}
    <flux:card variant="default">
        <flux:heading size="xl" level="2">Expiration Monitoring</flux:heading>
        <flux:text class="mt-1">Track batches nearing their expiration date and write off expired stock.</flux:text>
    </flux:card>

    {{-- Summary tiles --}}
    <x-skeleton.stat-tiles :count="4" :labels="['Expired', 'Within 7 days', 'Within 30 days', 'Within 90 days']" />

    {{-- Value at risk by month --}}
    <x-skeleton.chart height="h-56" heading="Value at risk" :action="false"
        description="Value of stock already expired, and expiring in each of the next six months" />

    {{-- Batch list --}}
    <flux:card>
        <flux:skeleton.group animate="pulse">
            <x-skeleton.heading heading="Tracked batches" :action="false"
                description="Batches with an expiration date and stock left. Tick expired batches to write several off at once." />

            <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center" aria-hidden="true">
                <div class="lg:flex-1">
                    <flux:skeleton class="h-8 w-full rounded-lg" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-52" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-52" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-44" />
                </div>
            </div>

            <x-skeleton.table-rows :rows="10" :columns="8" />
        </flux:skeleton.group>
    </flux:card>
</div>
