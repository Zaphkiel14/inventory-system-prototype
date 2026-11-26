<flux:card variant="default">

    <div class="mb-4">
        <flux:heading size="xl" level="2">Stock Adjustments</flux:heading>
        <flux:text class="mt-1">
            Permanent record of damage, losses, expiry write-offs, count corrections and returns.
        </flux:text>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm" placeholder="Search item, batch, lot, reason, names"
                wire:model.live.debounce.500ms="search" />
        </div>

        <div class="flex items-center gap-2">
            <flux:select size="sm" class="md:w-48" wire:model.live="type">
                <flux:select.option value="">All types</flux:select.option>
                @foreach (\App\Enums\AdjustmentType::cases() as $option)
                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:modal.trigger name="create-adjustment">
                <flux:button size="sm" icon="plus">New Adjustment</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @livewire('inventory.adjustment-create-form')

    <div x-data="{ open: null, toggle(id) { this.open = this.open === id ? null : id } }">
    <flux:table :paginate="$this->adjustments" class="transition-opacity"
        wire:loading.delay.class="opacity-60" wire:target="search,type,gotoPage,nextPage,previousPage,setPage">
        <flux:table.columns>
            <flux:table.column class="w-8"><span class="sr-only">Details</span></flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Item / Batch</flux:table.column>
            <flux:table.column align="end">Previous</flux:table.column>
            <flux:table.column align="end">Change</flux:table.column>
            <flux:table.column align="end">New</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column>Approved By</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->adjustments as $adjustment)
                @php $batch = $adjustment->inventoryBatch; @endphp

                <flux:table.row :key="$adjustment->id">
                    <flux:table.cell>
                        <flux:button size="xs" variant="ghost" square x-on:click="toggle({{ $adjustment->id }})"
                            x-bind:aria-expanded="open === {{ $adjustment->id }}"
                            aria-controls="adjustment-details-{{ $adjustment->id }}"
                            aria-label="Show details for this adjustment">
                            <flux:icon.chevron-right variant="micro" class="transition-transform"
                                x-bind:class="open === {{ $adjustment->id }} && 'rotate-90'" />
                        </flux:button>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $adjustment->created_at->format('M d, Y') }}
                        <flux:text size="sm">{{ $adjustment->created_at->format('h:i A') }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($adjustment->type)
                            <flux:badge size="sm" :color="$adjustment->type->color()">
                                {{ $adjustment->type->label() }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:text variant="strong" class="font-medium">{{ $batch?->inventoryItem?->name ?? '—' }}</flux:text>
                        <flux:text size="sm">
                            {{ $batch?->batch_number ?? '—' }} · {{ $batch?->lot_number ?? '—' }}
                        </flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end">{{ number_format($adjustment->previous_qty) }}</flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:text inline :color="$adjustment->changed_qty < 0 ? 'red' : 'green'" class="font-medium">
                            {{ $adjustment->changed_qty > 0 ? '+' : '' }}{{ number_format($adjustment->changed_qty) }}
                        </flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end" variant="strong">{{ number_format($adjustment->new_qty) }}</flux:table.cell>

                    <flux:table.cell class="max-w-64">
                        <button type="button" class="block w-full cursor-pointer text-start"
                            x-on:click="toggle({{ $adjustment->id }})">
                            <flux:text class="truncate">{{ $adjustment->reason }}</flux:text>
                        </button>
                    </flux:table.cell>

                    <flux:table.cell>{{ $adjustment->requested_by ?: '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $adjustment->approved_by ?: '—' }}</flux:table.cell>
                </flux:table.row>

                {{-- Expanded details --}}
                <flux:table.row :key="$adjustment->id.'-details'" id="adjustment-details-{{ $adjustment->id }}"
                    x-show="open === {{ $adjustment->id }}" style="display: none">
                    <flux:table.cell colspan="10" class="whitespace-normal">
                        <div class="grid gap-6 rounded-lg bg-zinc-50 p-4 md:grid-cols-3 dark:bg-white/5">
                            <div class="space-y-2 md:col-span-2">
                                <flux:text size="sm">Reason</flux:text>
                                <flux:text variant="strong" class="whitespace-pre-line break-words">{{ $adjustment->reason }}</flux:text>

                                <flux:text size="sm" class="pt-2">
                                    Adjustments are permanent. To fix a mistake, record a new adjustment.
                                </flux:text>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                    @foreach ([
                                        'Batch No.' => $batch?->batch_number,
                                        'Lot No.' => $batch?->lot_number,
                                        'Location' => $batch?->storageLocation?->name,
                                        'Expiration' => $batch?->expiration_date?->format('M d, Y'),
                                        'Requested By' => $adjustment->requested_by ?: null,
                                        'Approved By' => $adjustment->approved_by ?: null,
                                        'Recorded By' => $adjustment->recordedBy?->name,
                                        'Recorded At' => $adjustment->created_at->format('M d, Y h:i A'),
                                    ] as $label => $value)
                                        <div class="min-w-0">
                                            <flux:text size="sm">{{ $label }}</flux:text>
                                            <flux:text variant="strong" class="break-words">{{ $value ?? '—' }}</flux:text>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($batch)
                                    <flux:button size="sm" icon="cube" class="w-full" wire:navigate
                                        :href="route('inventory.stocks', ['batch' => $batch->id])">
                                        View in Inventory Stock
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="clipboard-document-list" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">No adjustments found</flux:heading>
                            <flux:text size="sm">
                                {{ $search !== '' || $type !== '' ? 'Try a different search or filter.' : 'Recorded adjustments will appear here.' }}
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    </div>
</flux:card>
