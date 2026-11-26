{{-- Placeholder for the deferred Adjustments component. Mirrors livewire/inventory/adjustments.blade.php. --}}
<flux:card variant="default">
    <span class="sr-only" role="status">Loading stock adjustments…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4">
            <flux:heading size="xl" level="2">Stock Adjustments</flux:heading>
            <flux:text class="mt-1">
                Permanent record of damage, losses, expiry write-offs, count corrections and returns.
            </flux:text>
        </div>

        <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center" aria-hidden="true">
            <div class="md:flex-1">
                <flux:skeleton class="h-8 w-full rounded-lg" />
            </div>

            <div class="flex items-center gap-2">
                <flux:skeleton class="h-8 flex-1 rounded-lg md:w-48 md:flex-none" />
                <flux:skeleton class="h-8 w-36 shrink-0 rounded-lg" />
            </div>
        </div>

        <x-skeleton.table-rows :rows="15" :columns="10" />

        <div class="mt-6 flex items-center justify-between gap-4" aria-hidden="true">
            <flux:skeleton.line class="w-40" />
            <flux:skeleton class="h-8 w-48 rounded-lg" />
        </div>
    </flux:skeleton.group>
</flux:card>
