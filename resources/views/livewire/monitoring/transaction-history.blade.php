@php
    use App\Enums\InventoryTransactionType;

    $totals = $this->totals;
    $period = $this->period;
    $filtered = $search !== '' || $type !== '' || $direction !== '' || $user !== '' || $range !== '30' || $order !== 'desc';
@endphp

<div class="flex flex-col gap-6">

    {{-- Header --}}
    <flux:card variant="default">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Transaction History</flux:heading>
                <flux:text class="mt-1">
                    Every receipt, issuance and adjustment in the inventory ledger
                    @if ($period)
                        from <span class="font-medium text-zinc-800 dark:text-white">{{ $period[0]->format('M j, Y') }}</span>
                        to <span class="font-medium text-zinc-800 dark:text-white">{{ $period[1]->format('M j, Y') }}</span>.
                    @else
                        across all time.
                    @endif
                </flux:text>
            </div>

            <flux:button size="sm" icon="arrow-down-tray" wire:click="export" :disabled="$totals['entries'] === 0">
                Export CSV
            </flux:button>
        </div>
    </flux:card>

    {{-- Totals for the current filters --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Entries', number_format($totals['entries']), 'queue-list', null],
            ['Units in', '+'.number_format($totals['in']), 'arrow-down-tray', 'text-emerald-600 dark:text-emerald-400'],
            ['Units out', '−'.number_format($totals['out']), 'arrow-up-tray', 'text-sky-600 dark:text-sky-400'],
            ['Net change', ($totals['net'] > 0 ? '+' : ($totals['net'] < 0 ? '−' : '')).number_format(abs($totals['net'])), 'scale', $totals['net'] < 0 ? 'text-red-600 dark:text-red-400' : null],
        ] as [$label, $value, $icon, $tone])
            <flux:card size="sm" class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <flux:text size="sm">{{ $label }}</flux:text>
                    <flux:icon :name="$icon" variant="micro" class="text-zinc-400" />
                </div>
                <flux:heading size="xl" @class(['tabular-nums', $tone ? $tone.'!' : null])>{{ $value }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    {{-- Ledger --}}
    <flux:card>
        <div class="mb-4 space-y-3">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="lg:flex-1">
                    <flux:input icon="magnifying-glass" size="sm"
                        placeholder="Search item, batch, lot, delivery, PO, issuance or request no."
                        wire:model.live.debounce.500ms="search" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:select size="sm" class="w-full sm:w-40" wire:model.live="range" aria-label="Period">
                        @foreach (\App\Livewire\Monitoring\TransactionHistory::RANGES as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-36" wire:model.live="type" aria-label="Type">
                        <flux:select.option value="">All types</flux:select.option>
                        @foreach (InventoryTransactionType::cases() as $case)
                            <flux:select.option value="{{ $case->key() }}">{{ $case->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-32" wire:model.live="direction" aria-label="Direction">
                        <flux:select.option value="">In &amp; out</flux:select.option>
                        <flux:select.option value="in">Stock in</flux:select.option>
                        <flux:select.option value="out">Stock out</flux:select.option>
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-44" wire:model.live="user" aria-label="Recorded by">
                        <flux:select.option value="">Anyone</flux:select.option>
                        @foreach ($this->users as $person)
                            <flux:select.option value="{{ $person->id }}">{{ $person->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:button size="sm" variant="ghost" wire:click="toggleOrder"
                        :icon="$order === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'"
                        :tooltip="$order === 'asc' ? 'Oldest first' : 'Newest first'" />

                    @if ($filtered)
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters" tooltip="Clear filters" />
                    @endif
                </div>
            </div>

            @if ($range === 'custom')
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">From</flux:text>
                    <x-date-picker size="sm" class="sm:w-44" wire:model.live="from" aria-label="From" align="end" clearable />
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">to</flux:text>
                    <x-date-picker size="sm" class="sm:w-44" wire:model.live="to" aria-label="To" align="end" clearable />
                </div>
            @endif
        </div>

        <div wire:loading.class="opacity-60" wire:target="search,range,from,to,type,direction,user,toggleOrder,clearFilters,gotoPage,nextPage,previousPage">
            @if ($this->transactions->isNotEmpty())
                <flux:table :paginate="$this->transactions">
                    <flux:table.columns>
                        <flux:table.column>Date</flux:table.column>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Item / Batch</flux:table.column>
                        <flux:table.column align="end">Quantity</flux:table.column>
                        <flux:table.column align="end">Balance after</flux:table.column>
                        <flux:table.column>Reference</flux:table.column>
                        <flux:table.column>Recorded by</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->transactions as $t)
                            @php $ref = $t->reference; @endphp
                            <flux:table.row wire:key="txn-{{ $t->id }}">
                                <flux:table.cell class="whitespace-nowrap">
                                    <div class="text-zinc-800 dark:text-white">{{ $t->created_at?->format('M d, Y') }}</div>
                                    <flux:text size="sm">{{ $t->created_at?->format('g:i A') }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($t->type)
                                        <flux:badge size="sm" :color="$t->type->color()" :icon="$t->type->icon()">{{ $t->type->label() }}</flux:badge>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    @if ($t->inventoryItem)
                                        <flux:link :href="route('monitoring.stock-monitoring', ['expanded' => $t->inventory_item_id, 'search' => $t->inventoryItem->name])"
                                            variant="ghost" class="font-medium" wire:navigate>{{ $t->inventoryItem->name }}</flux:link>
                                    @else
                                        <span class="text-zinc-400">Unknown item</span>
                                    @endif
                                    <flux:text size="sm">
                                        {{ $t->inventoryItem?->item_code }}
                                        @if ($t->inventoryBatch?->batch_number) · {{ $t->inventoryBatch->batch_number }} @endif
                                    </flux:text>
                                </flux:table.cell>

                                <flux:table.cell align="end" class="whitespace-nowrap tabular-nums">
                                    <span @class([
                                        'font-semibold',
                                        'text-emerald-600 dark:text-emerald-400' => $t->quantity > 0,
                                        'text-red-600 dark:text-red-400' => $t->quantity < 0,
                                    ])>{{ $t->quantity > 0 ? '+' : ($t->quantity < 0 ? '−' : '') }}{{ number_format(abs($t->quantity)) }}</span>
                                    <span class="text-zinc-400">{{ $t->inventoryItem?->unit }}</span>
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($t->balance_after) }}</flux:table.cell>

                                <flux:table.cell class="max-w-72">
                                    @if ($ref)
                                        <div class="truncate text-zinc-800 dark:text-white">{{ $ref['label'] }}</div>
                                        @if (filled($ref['detail']))
                                            <flux:text size="sm" class="truncate" title="{{ $ref['detail'] }}">{{ $ref['detail'] }}</flux:text>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">{{ $t->user?->name ?? '—' }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                    <flux:icon name="queue-list" variant="outline" class="size-8 text-zinc-400" />
                    <flux:heading size="sm">No transactions found</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ $filtered ? 'Try a different search, period or filter.' : 'Receipts, issuances and adjustments will appear here.' }}
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>
</div>
