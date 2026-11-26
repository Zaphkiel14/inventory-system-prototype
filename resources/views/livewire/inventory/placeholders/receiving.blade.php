{{-- Placeholder for the deferred Receiving component. Mirrors livewire/inventory/receiving.blade.php. --}}
<flux:card variant="default">
    <span class="sr-only" role="status">Loading receipts…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="2">Receiving Inventory Stock</flux:heading>
                <flux:text class="mt-1">Manage incoming inventory item batches.</flux:text>
            </div>

            <div class="flex items-center gap-2" aria-hidden="true">
                <flux:skeleton class="h-8 w-64 rounded-lg" />
                <flux:skeleton class="h-8 w-40 rounded-lg" />
                <flux:skeleton class="h-8 w-32 rounded-lg" />
            </div>
        </div>

        <x-skeleton.table-rows :rows="10" :columns="9" />
    </flux:skeleton.group>
</flux:card>
