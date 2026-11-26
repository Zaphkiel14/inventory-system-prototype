{{-- Placeholder for a lazy StockDetail row. Mirrors livewire/monitoring/partials/stock-detail.blade.php
     (trend chart h-44 + movement tiles on the left, batch table on the right) so the row doesn't jump. --}}
<div class="grid gap-6 p-4 2xl:grid-cols-5">
    <span class="sr-only" role="status">Loading item details…</span>

    <flux:skeleton.group animate="pulse" class="flex flex-col gap-4 2xl:col-span-2" aria-hidden="true">
        <div class="flex flex-col gap-1">
            <flux:skeleton.line class="w-48" />
            <flux:skeleton.line class="w-72 max-w-full" />
        </div>

        <flux:skeleton class="h-44 w-full rounded-lg" />

        <div class="grid grid-cols-3 gap-3">
            @foreach (range(1, 3) as $tile)
                <flux:skeleton class="h-14 rounded-lg" />
            @endforeach
        </div>

        <flux:skeleton.line class="w-3/4" />
    </flux:skeleton.group>

    <flux:skeleton.group animate="pulse" class="flex flex-col gap-3 2xl:col-span-3" aria-hidden="true">
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-1 flex-col gap-1">
                <flux:skeleton.line class="w-40" />
                <flux:skeleton.line class="w-64 max-w-full" />
            </div>
            <flux:skeleton class="h-5 w-12 rounded-md" />
        </div>

        <div class="flex flex-col divide-y divide-zinc-100 rounded-lg border border-zinc-200 bg-white dark:divide-white/5 dark:border-white/10 dark:bg-white/5">
            @foreach (range(1, 4) as $row)
                <div class="flex items-center gap-4 px-3 py-2.5">
                    <flux:skeleton.line class="w-4" />
                    <flux:skeleton.line class="flex-1" />
                    <flux:skeleton.line class="w-16" />
                    <flux:skeleton.line class="w-12" />
                </div>
            @endforeach
        </div>
    </flux:skeleton.group>
</div>
