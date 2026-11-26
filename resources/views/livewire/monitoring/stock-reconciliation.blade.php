@php
    use App\Livewire\Monitoring\StockReconciliation as Page;

    $rows = $this->rows;
    $exceptions = $this->exceptions;
    $totals = $this->totals;
    [$start, $end] = $this->period;
    $filtered = $range !== '30' || $category !== null || $location !== null || $search !== '' || $status !== 'all';

    $kinds = array_map(fn ($k) => $k[0], Page::EXCEPTION_KINDS);
    $historyUrl = fn (?string $item) => route('monitoring.transaction-history', ['search' => $item, 'range' => 'custom', 'from' => $start->toDateString(), 'to' => $end->toDateString()]);
    $signed = fn ($n) => ($n > 0 ? '+' : ($n < 0 ? '−' : '')).number_format(abs($n));
    $allGood = $totals['items'] > 0 && $totals['short'] === 0 && $totals['overflow'] === 0 && $totals['unverified'] === 0 && $exceptions->isEmpty();
@endphp

<div class="flex flex-col gap-6">

    {{-- Header --}}
    <flux:card variant="default">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Stock Reconciliation</flux:heading>
                <flux:text class="mt-1">
                    Checks that opening + received − issued ± adjusted equals the stock actually on hand, and flags ledger entries that do not add up.
                </flux:text>
            </div>

            <flux:button size="sm" icon="document-arrow-down" wire:click="exportPdf" wire:loading.attr="disabled" wire:target="exportPdf">
                <span wire:loading.remove wire:target="exportPdf">Export PDF</span>
                <span wire:loading wire:target="exportPdf">Preparing…</span>
            </flux:button>
        </div>
    </flux:card>

    {{-- KPIs --}}
    <div @class(['grid gap-4 sm:grid-cols-2', 'xl:grid-cols-5' => $totals['unverified'] === 0, 'xl:grid-cols-6' => $totals['unverified'] > 0])>
        @foreach ([
            ['Items checked', $totals['items'], 'clipboard-document-check', null],
            ['Balanced', $totals['balanced'], 'check-circle', 'text-emerald-600 dark:text-emerald-400'],
            ['Short', $totals['short'], 'arrow-trending-down', 'text-red-600 dark:text-red-400'],
            ['Overflow', $totals['overflow'], 'arrow-trending-up', 'text-amber-600 dark:text-amber-400'],
            ...($totals['unverified'] > 0 ? [['Unverified', $totals['unverified'], 'question-mark-circle', 'text-zinc-500 dark:text-zinc-400']] : []),
            ['Exceptions', $exceptions->count(), 'exclamation-triangle', $exceptions->isNotEmpty() ? 'text-red-600 dark:text-red-400' : null],
        ] as [$label, $value, $icon, $tone])
            <flux:card size="sm" class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <flux:text size="sm">{{ $label }}</flux:text>
                    <flux:icon :name="$icon" variant="micro" class="text-zinc-400" />
                </div>
                <flux:heading size="xl" @class(['tabular-nums', $tone ? $tone.'!' : null])>{{ number_format($value) }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    @if ($allGood)
        <flux:callout variant="success" icon="check-circle" heading="All stock accounted for in this period." />
    @endif

    {{-- Filters --}}
    <flux:card>
        <div class="space-y-3">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="lg:flex-1">
                    <flux:input icon="magnifying-glass" size="sm" placeholder="Search item"
                        wire:model.live.debounce.300ms="search" />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:select size="sm" class="w-full sm:w-40" wire:model.live="range" aria-label="Period">
                        @foreach (Page::RANGES as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-40" wire:model.live="category" aria-label="Category">
                        <flux:select.option value="">All categories</flux:select.option>
                        @foreach ($this->categories as $id => $name)
                            <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-40" wire:model.live="location" aria-label="Location">
                        <flux:select.option value="">All locations</flux:select.option>
                        @foreach ($this->locations as $id => $name)
                            <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="w-full sm:w-40" wire:model.live="status" aria-label="Status">
                        @foreach (Page::STATUSES as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if ($filtered)
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters">Clear filters</flux:button>
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

            <flux:text size="sm">
                Showing <span class="font-medium text-zinc-800 dark:text-white">{{ $start->format('M j, Y') }}</span>
                – <span class="font-medium text-zinc-800 dark:text-white">{{ $end->format('M j, Y') }}</span>.
                @if (! $this->asOfToday)
                    <span class="text-zinc-500 dark:text-zinc-400">Actual = ledger balance recorded at end of period (batches only hold current stock).</span>
                @endif
            </flux:text>
        </div>
    </flux:card>

    {{-- Items --}}
    <flux:card>
        <div wire:loading.class="opacity-60" wire:target="search,range,from,to,category,location,status,clearFilters,gotoPage,nextPage,previousPage">
            @if ($rows->isNotEmpty())
                <flux:table :paginate="$this->pagedRows">
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="end">Opening</flux:table.column>
                        <flux:table.column align="end">Received (+)</flux:table.column>
                        <flux:table.column align="end">Issued (−)</flux:table.column>
                        <flux:table.column align="end">Adjusted (±)</flux:table.column>
                        <flux:table.column align="end">Expected</flux:table.column>
                        <flux:table.column align="end">Actual</flux:table.column>
                        <flux:table.column align="end">Variance</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Exceptions</flux:table.column>
                        <flux:table.column><span class="sr-only">Actions</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->pagedRows as $row)
                            <flux:table.row :key="$row['item_id']">
                                <flux:table.cell>
                                    <div class="font-medium text-zinc-800 dark:text-white">{{ $row['name'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['unit'] }}</div>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['opening']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['received']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($row['issued']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ $signed($row['adjusted']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums font-medium">{{ number_format($row['expected']) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums font-medium">
                                    {{ $row['actual'] === null ? '—' : number_format($row['actual']) }}
                                </flux:table.cell>
                                <flux:table.cell align="end" @class([
                                    'tabular-nums font-medium',
                                    'text-red-600 dark:text-red-400' => $row['variance'] !== null && $row['variance'] < 0,
                                    'text-amber-600 dark:text-amber-400' => $row['variance'] !== null && $row['variance'] > 0,
                                ])>
                                    {{ $row['variance'] === null ? '—' : $signed($row['variance']) }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($row['status'] === 'unverified')
                                        <flux:tooltip content="No per-location balance history for past dates">
                                            <div class="inline-flex"><flux:badge size="sm" color="zinc">Unverified</flux:badge></div>
                                        </flux:tooltip>
                                    @else
                                        <flux:badge size="sm" :color="match ($row['status']) { 'balanced' => 'green', 'short' => 'red', default => 'amber' }">
                                            {{ ucfirst($row['status']) }}
                                        </flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($row['exception_count'] > 0)
                                        @php
                                            $shown = array_slice($row['exception_kinds'], 0, 2, true);
                                            $hidden = array_slice($row['exception_kinds'], 2, null, true);
                                        @endphp
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach ($shown as $kind => $count)
                                                <flux:tooltip :content="Page::EXCEPTION_KINDS[$kind][2] ?? $kind">
                                                    <div class="inline-flex">
                                                        <flux:badge size="sm" color="red">{{ Page::EXCEPTION_KINDS[$kind][1] ?? $kind }} ×{{ $count }}</flux:badge>
                                                    </div>
                                                </flux:tooltip>
                                            @endforeach
                                            @if ($hidden)
                                                <flux:tooltip :content="collect($hidden)->map(fn ($c, $k) => (Page::EXCEPTION_KINDS[$k][1] ?? $k).' ×'.$c)->join(', ')">
                                                    <div class="inline-flex">
                                                        <flux:badge size="sm" color="zinc">+{{ count($hidden) }}</flux:badge>
                                                    </div>
                                                </flux:tooltip>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions for {{ $row['name'] }}" />

                                        <flux:menu>
                                            @if ($row['exception_count'] > 0)
                                                <flux:menu.item icon="exclamation-triangle" wire:click="showExceptions({{ $row['item_id'] }})">
                                                    View exceptions ({{ $row['exception_count'] }})
                                                </flux:menu.item>
                                            @endif
                                            <flux:menu.item icon="arrows-right-left" :href="$historyUrl($row['name'])" wire:navigate>
                                                View transaction history
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="flex flex-col items-center gap-2 py-12 text-center">
                    <flux:icon name="clipboard-document-check" class="size-8 text-zinc-400" />
                    <flux:heading>No items to reconcile</flux:heading>
                    <flux:text size="sm">Try a different period or clear the filters.</flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Exceptions --}}
    @if ($exceptions->isNotEmpty())
        {{-- Pages on its own query-string key (StockReconciliation::EXCEPTIONS_PAGE_NAME), separate from the item table.
             Counts and conditions elsewhere still read the full $exceptions list. --}}
        @php $pagedExceptions = $this->pagedExceptions; @endphp
        <flux:card>
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg" level="3">Ledger exceptions</flux:heading>
                    <flux:text size="sm">Entries and batches whose numbers do not add up.</flux:text>
                </div>
                <flux:badge size="sm" color="red" class="shrink-0 tabular-nums">{{ number_format($pagedExceptions->total()) }}</flux:badge>
            </div>

            <flux:table :paginate="$pagedExceptions">
                <flux:table.columns>
                    <flux:table.column>Issue</flux:table.column>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column>Batch</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column align="end">Expected</flux:table.column>
                    <flux:table.column align="end">Actual</flux:table.column>
                    <flux:table.column align="end">Difference</flux:table.column>
                    <flux:table.column>Details</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($pagedExceptions as $i => $ex)
                        <flux:table.row :key="'ex-'.$pagedExceptions->currentPage().'-'.$i">
                            <flux:table.cell>
                                <flux:badge size="sm" color="red">{{ $kinds[$ex['kind']] ?? $ex['kind'] }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <a class="font-medium text-zinc-800 hover:underline dark:text-white" wire:navigate
                                    href="{{ $historyUrl($ex['item_name']) }}">{{ $ex['item_name'] }}</a>
                            </flux:table.cell>
                            <flux:table.cell>{{ $ex['batch_number'] ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $ex['occurred_at']?->format('M j, Y h:i A') }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $ex['expected'] === null ? '—' : number_format($ex['expected']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $ex['actual'] === null ? '—' : number_format($ex['actual']) }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums font-medium text-red-600 dark:text-red-400">{{ $ex['difference'] === null ? '—' : $signed($ex['difference']) }}</flux:table.cell>
                            <flux:table.cell class="min-w-64 whitespace-normal">{{ $ex['message'] }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif

    {{-- Per-item exceptions flyout --}}
    <flux:modal name="item-exceptions" variant="flyout" class="w-full md:w-[480px]">
        @php
            $itemExceptions = $this->itemExceptions;
            $itemName = $itemExceptions->first()['item_name'] ?? null;
        @endphp

        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Exceptions{{ $itemName ? ' — '.$itemName : '' }}</flux:heading>
                <flux:text size="sm" class="mt-1">
                    {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}
                </flux:text>
            </div>

            @forelse ($itemExceptions as $i => $ex)
                <flux:card size="sm" class="space-y-3" wire:key="item-ex-{{ $i }}">
                    <div class="flex items-start justify-between gap-2">
                        <flux:badge size="sm" color="red">{{ $kinds[$ex['kind']] ?? $ex['kind'] }}</flux:badge>
                        <flux:text size="sm" class="whitespace-nowrap">{{ $ex['occurred_at']?->format('M j, Y h:i A') }}</flux:text>
                    </div>

                    <flux:text size="sm">{{ Page::EXCEPTION_KINDS[$ex['kind']][2] ?? '' }}</flux:text>

                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div>
                            <flux:text size="sm">Expected</flux:text>
                            <div class="font-medium tabular-nums text-zinc-800 dark:text-white">{{ $ex['expected'] === null ? '—' : number_format($ex['expected']) }}</div>
                        </div>
                        <div>
                            <flux:text size="sm">Actual</flux:text>
                            <div class="font-medium tabular-nums text-zinc-800 dark:text-white">{{ $ex['actual'] === null ? '—' : number_format($ex['actual']) }}</div>
                        </div>
                        <div>
                            <flux:text size="sm">Difference</flux:text>
                            <div class="font-medium tabular-nums text-red-600 dark:text-red-400">{{ $ex['difference'] === null ? '—' : $signed($ex['difference']) }}</div>
                        </div>
                    </div>

                    <flux:separator variant="subtle" />

                    <div class="space-y-1">
                        <flux:text size="sm">
                            Batch <span class="font-medium text-zinc-800 dark:text-white">{{ $ex['batch_number'] ?? '—' }}</span>
                            @if ($ex['transaction_id'])
                                · Transaction <span class="font-medium text-zinc-800 dark:text-white">#{{ $ex['transaction_id'] }}</span>
                            @endif
                        </flux:text>
                        <flux:text size="sm">{{ $ex['message'] }}</flux:text>
                    </div>
                </flux:card>
            @empty
                <flux:text size="sm">No exceptions for this item in the selected period.</flux:text>
            @endforelse

            @if ($itemName)
                <flux:button size="sm" icon="arrows-right-left" class="w-full" :href="$historyUrl($itemName)" wire:navigate>
                    View transaction history
                </flux:button>
            @endif
        </div>
    </flux:modal>
</div>
