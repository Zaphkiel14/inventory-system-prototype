<flux:card variant="default">

    <div class="mb-4">
        <flux:heading size="xl" level="2">Inventory Stock</flux:heading>
        <flux:text class="mt-1">Browse every received batch and its remaining quantity.</flux:text>
    </div>

    {{-- Toolbar: search + filters side by side --}}
    <div class="mb-6 space-y-3">
        <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <div class="md:flex-1">
                <flux:input icon="magnifying-glass" size="sm" placeholder="Search item, batch, lot, ref, PO"
                    wire:model.live.debounce.500ms="search" />
            </div>

            <div class="flex items-center gap-2">
                <flux:select size="sm" class="md:w-52" wire:model.live="expiry">
                    @foreach (\App\Livewire\Inventory\Stocks::EXPIRY_FILTERS as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" class="md:w-48" wire:model.live="sortBy">
                    @foreach (\App\Livewire\Inventory\Stocks::SORTABLE as $key => $option)
                        <flux:select.option value="{{ $key }}">Sort: {{ $option['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button size="sm" variant="ghost" wire:click="toggleSortDirection"
                    :icon="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                    :tooltip="$sortDirection === 'asc' ? 'Ascending' : 'Descending'" />

                @if ($search !== '' || $expiry !== '')
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters"
                        tooltip="Clear filters" />
                @endif
            </div>
        </div>

        @if ($expiry === 'custom')
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                <flux:text size="sm" class="text-zinc-500">Expires between</flux:text>
                <flux:input type="date" size="sm" class="sm:w-44" wire:model.live="expiryFrom" />
                <flux:text size="sm" class="text-zinc-500">and</flux:text>
                <flux:input type="date" size="sm" class="sm:w-44" wire:model.live="expiryTo" />
            </div>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60">
        @forelse ($this->batches as $batch)
            @php
                $item = $batch->inventoryItem;
                $receipt = $batch->stockReceipt;
                $pct = $batch->received_qty > 0
                    ? (int) round($batch->remaining_qty / $batch->received_qty * 100)
                    : 0;

                if ($batch->remaining_qty <= 0) {
                    [$stateLabel, $stateColor] = ['Depleted', 'zinc'];
                } elseif ($batch->expiration_date?->isPast()) {
                    [$stateLabel, $stateColor] = ['Expired', 'red'];
                } elseif ($batch->expiration_date && $batch->expiration_date->lte(today()->addDays(30))) {
                    [$stateLabel, $stateColor] = ['Expiring Soon', 'amber'];
                } else {
                    [$stateLabel, $stateColor] = ['In Stock', 'green'];
                }
            @endphp

            @php
                $identifiers = [
                    'Batch No.' => $batch->batch_number,
                    'Lot No.' => $batch->lot_number,
                    'Reference No.' => $receipt?->reference_number,
                    'PO No.' => $receipt?->purchase_order_number,
                ];

                $details = [
                    'Supplier' => [$receipt?->supplier?->name, null],
                    'Location' => [$batch->storageLocation?->name, null],
                    'Received' => [$batch->received_at?->format('M d, Y'), null],
                    'Unit Cost' => [number_format((float) $batch->unit_cost, 2), null],
                    'Manufactured' => [$batch->manufactured_date?->format('M d, Y'), null],
                    'Expiration' => [
                        $batch->expiration_date?->format('M d, Y'),
                        in_array($stateColor, ['red', 'amber'], true) ? $stateColor : null,
                    ],
                ];
            @endphp

            <flux:card size="sm" wire:key="batch-{{ $batch->id }}" class="flex flex-col gap-4">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <flux:text size="sm">{{ $item?->item_code }}</flux:text>
                        <flux:heading class="truncate">{{ $item?->name ?? 'Unknown item' }}</flux:heading>
                    </div>
                    <flux:badge size="sm" :color="$stateColor">{{ $stateLabel }}</flux:badge>
                </div>

                {{-- Quantity --}}
                <div class="space-y-2">
                    <div class="flex items-baseline justify-between gap-2">
                        <flux:heading size="xl">
                            {{ number_format($batch->remaining_qty) }}
                            <flux:text inline>{{ $item?->unit }}</flux:text>
                        </flux:heading>
                        <flux:text size="sm">of {{ number_format($batch->received_qty) }} received</flux:text>
                    </div>
                    <flux:progress :value="$pct" :color="$pct > 25 ? 'emerald' : 'amber'" />
                </div>

                {{-- Identifiers --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                    @foreach ($identifiers as $label => $value)
                        <div class="min-w-0">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:text variant="strong" class="font-medium break-all">{{ $value ?? '—' }}</flux:text>
                        </div>
                    @endforeach
                </div>

                <flux:separator variant="subtle" />

                {{-- Details --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                    @foreach ($details as $label => [$value, $color])
                        <div class="min-w-0">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:text :variant="$color ? null : 'strong'" :color="$color" class="truncate">
                                {{ $value ?? '—' }}
                            </flux:text>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @empty
            <div class="col-span-full flex flex-col items-center justify-center gap-2 py-12 text-center">
                <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />
                <flux:heading size="sm">No batches found</flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    {{ $search !== '' || $expiry !== '' ? 'Try a different search or filter.' : 'Received stock will appear here.' }}
                </flux:text>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        <flux:pagination :paginator="$this->batches" />
    </div>
</flux:card>
