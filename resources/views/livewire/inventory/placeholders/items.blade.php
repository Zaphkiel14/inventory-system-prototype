{{-- Placeholder for the deferred Items component. Mirrors livewire/inventory/items.blade.php. --}}
<flux:card variant="default">
    <span class="sr-only" role="status">Loading inventory items…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="2">Inventory Items</flux:heading>
                <flux:text class="mt-1">Manage your inventory items.</flux:text>
            </div>

            <div class="flex items-center gap-2" aria-hidden="true">
                <flux:skeleton class="h-8 w-64 rounded-lg" />
                <flux:skeleton class="h-8 w-24 rounded-lg" />
            </div>
        </div>

        <x-skeleton.table-rows :rows="10" :columns="8" />

        <div class="mt-6 flex items-center justify-between gap-4" aria-hidden="true">
            <flux:skeleton.line class="w-40" />
            <flux:skeleton class="h-8 w-48 rounded-lg" />
        </div>
    </flux:skeleton.group>
</flux:card>
