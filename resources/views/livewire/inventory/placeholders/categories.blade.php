{{-- Placeholder for the deferred Categories component. Mirrors livewire/inventory/categories.blade.php. --}}
<flux:card>
    <span class="sr-only" role="status">Loading categories…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="2">Inventory Categories</flux:heading>
                <flux:text class="mt-1">Manage your inventory Categories.</flux:text>
            </div>

            <div class="flex items-center gap-2" aria-hidden="true">
                <flux:skeleton class="h-8 w-64 rounded-lg" />
                <flux:skeleton class="h-8 w-32 rounded-lg" />
            </div>
        </div>

        <x-skeleton.table-rows :rows="10" :columns="4" />

        <div class="mt-6 flex items-center justify-between gap-4" aria-hidden="true">
            <flux:skeleton.line class="w-40" />
            <flux:skeleton class="h-8 w-48 rounded-lg" />
        </div>
    </flux:skeleton.group>
</flux:card>
