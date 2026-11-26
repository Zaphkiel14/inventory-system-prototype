{{-- Placeholder for the deferred StockReconciliation component. Mirrors livewire/monitoring/stock-reconciliation.blade.php. --}}
<div class="flex flex-col gap-6">
    <span class="sr-only" role="status">Loading stock reconciliation…</span>

    {{-- Header --}}
    <flux:card variant="default">
        <flux:skeleton.group animate="pulse" class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Stock Reconciliation</flux:heading>
                <flux:text class="mt-1">
                    Checks that opening + received − issued ± adjusted equals the stock actually on hand, and flags ledger entries that do not add up.
                </flux:text>
            </div>

            <flux:skeleton class="h-8 w-28 shrink-0 rounded-lg" aria-hidden="true" />
        </flux:skeleton.group>
    </flux:card>

    {{-- KPIs (the live page adds an "Unverified" tile only for past periods) --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach (['Items checked', 'Balanced', 'Short', 'Overflow', 'Exceptions'] as $label)
            <flux:card size="sm" class="flex flex-col gap-2">
                <flux:skeleton.group animate="pulse" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <flux:text size="sm">{{ $label }}</flux:text>
                        <flux:skeleton class="size-4 rounded" aria-hidden="true" />
                    </div>
                    <flux:skeleton class="h-8 w-16" aria-hidden="true" />
                </flux:skeleton.group>
            </flux:card>
        @endforeach
    </div>

    {{-- Filters --}}
    <flux:card>
        <flux:skeleton.group animate="pulse" class="space-y-3" aria-hidden="true">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="lg:flex-1">
                    <flux:skeleton class="h-8 w-full rounded-lg" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-40" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-40" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-40" />
                    <flux:skeleton class="h-8 w-full rounded-lg sm:w-40" />
                </div>
            </div>

            <flux:skeleton.line class="w-56" />
        </flux:skeleton.group>
    </flux:card>

    {{-- Items --}}
    <flux:card>
        <flux:skeleton.group animate="pulse">
            <x-skeleton.table-rows :rows="10" :columns="10" />
        </flux:skeleton.group>
    </flux:card>
</div>
