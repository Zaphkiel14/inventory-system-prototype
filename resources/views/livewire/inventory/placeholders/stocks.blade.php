{{-- Placeholder for the deferred Stocks component. Mirrors livewire/inventory/stocks.blade.php. --}}
<flux:card variant="default">
    <span class="sr-only" role="status">Loading stock batches…</span>

    <flux:skeleton.group animate="pulse">
        <div class="mb-4">
            <flux:heading size="xl" level="2">Inventory Stock</flux:heading>
            <flux:text class="mt-1">Browse every received batch and its remaining quantity.</flux:text>
        </div>

        {{-- Toolbar --}}
        <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center" aria-hidden="true">
            <div class="md:flex-1">
                <flux:skeleton class="h-8 w-full rounded-lg" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:skeleton class="h-8 w-full rounded-lg sm:w-48" />
                <flux:skeleton class="h-8 w-full rounded-lg md:w-52" />
                <flux:skeleton class="h-8 w-full rounded-lg md:w-48" />
                <flux:skeleton class="size-8 rounded-lg" />
            </div>
        </div>

        {{-- Batch cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (range(1, 6) as $card)
                <flux:card size="sm" class="flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3" aria-hidden="true">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <flux:skeleton.line class="w-20" />
                            <flux:skeleton.line size="lg" class="w-3/4" />
                            <flux:skeleton class="h-5 w-16 rounded-md" />
                        </div>
                        <flux:skeleton class="h-5 w-16 shrink-0 rounded-md" />
                    </div>

                    <div class="space-y-2" aria-hidden="true">
                        <div class="flex items-baseline justify-between gap-2">
                            <flux:skeleton class="h-8 w-24" />
                            <flux:skeleton.line class="w-28" />
                        </div>
                        <flux:skeleton class="h-1.5 w-full rounded-full" />
                    </div>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                        @foreach (['Batch No.', 'Lot No.', 'Reference No.', 'PO No.'] as $label)
                            <div class="min-w-0">
                                <flux:text size="sm">{{ $label }}</flux:text>
                                <flux:skeleton.line class="w-2/3" aria-hidden="true" />
                            </div>
                        @endforeach
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                        @foreach (['Supplier', 'Location', 'Received', 'Unit Cost', 'Manufactured', 'Expiration'] as $label)
                            <div class="min-w-0">
                                <flux:text size="sm">{{ $label }}</flux:text>
                                <flux:skeleton.line class="w-2/3" aria-hidden="true" />
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between gap-4" aria-hidden="true">
            <flux:skeleton.line class="w-40" />
            <flux:skeleton class="h-8 w-48 rounded-lg" />
        </div>
    </flux:skeleton.group>
</flux:card>
