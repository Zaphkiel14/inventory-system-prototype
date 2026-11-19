@php
    use App\Enums\StockStatus;

    $counts = $this->counts;
    $total = array_sum($counts);
    $filtered = $search !== '' || $status !== '' || $category !== '' || $sort !== 'urgency';

    $tiles = [
        StockStatus::Out->value => ['icon' => 'x-circle', 'text' => 'text-red-600 dark:text-red-400', 'ring' => 'ring-red-500/60 dark:ring-red-400/60'],
        StockStatus::Critical->value => ['icon' => 'exclamation-triangle', 'text' => 'text-orange-600 dark:text-orange-400', 'ring' => 'ring-orange-500/60 dark:ring-orange-400/60'],
        StockStatus::Reorder->value => ['icon' => 'shopping-cart', 'text' => 'text-amber-600 dark:text-amber-400', 'ring' => 'ring-amber-500/60 dark:ring-amber-400/60'],
        StockStatus::Healthy->value => ['icon' => 'check-circle', 'text' => 'text-emerald-600 dark:text-emerald-400', 'ring' => 'ring-emerald-500/60 dark:ring-emerald-400/60'],
    ];

    // Literal classes for the stock bar so Tailwind compiles them.
    $barColor = [
        'red' => 'bg-red-500 dark:bg-red-400',
        'orange' => 'bg-orange-500 dark:bg-orange-400',
        'amber' => 'bg-amber-500 dark:bg-amber-400',
        'green' => 'bg-emerald-500 dark:bg-emerald-400',
    ];
@endphp

<div class="flex flex-col gap-6">

    {{-- Header --}}
    <flux:card variant="default">
        <flux:heading size="xl" level="2">Stock Monitoring</flux:heading>
        <flux:text class="mt-1">
            Usable, committed and free stock per item against its thresholds. Expand an item to see its batches in issue order.
        </flux:text>
    </flux:card>

    {{-- Status tiles (click to filter) --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (StockStatus::cases() as $case)
            @php
                $tile = $tiles[$case->value];
                $count = $counts[$case->value];
                $active = $status === $case->value;
                $pct = $total ? round($count / $total * 100) : 0;
            @endphp
            <button type="button" wire:click="filterStatus('{{ $case->value }}')" aria-pressed="{{ $active ? 'true' : 'false' }}"
                @class([
                    'rounded-xl text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent',
                    'ring-2 '.$tile['ring'] => $active,
                ])>
                <flux:card size="sm" class="flex h-full flex-col gap-2 hover:bg-zinc-50 dark:hover:bg-white/5">
                    <div class="flex items-center justify-between gap-2">
                        <flux:text size="sm">{{ $case->label() }}</flux:text>
                        <flux:icon :name="$tile['icon']" variant="micro" @class([$tile['text'] => $count > 0, 'text-zinc-400' => $count === 0]) />
                    </div>

                    <div class="flex items-baseline gap-2">
                        <flux:heading size="xl" @class(['tabular-nums', $tile['text'].'!' => $count > 0 && $case !== StockStatus::Healthy])>
                            {{ number_format($count) }}
                        </flux:heading>
                        <flux:text size="sm">{{ Str::plural('item', $count) }} · {{ $pct }}%</flux:text>
                    </div>

                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $case->description() }}</flux:text>
                </flux:card>
            </button>
        @endforeach
    </div>

    {{-- Item list --}}
    <flux:card>
        <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="lg:flex-1">
                <flux:input icon="magnifying-glass" size="sm" placeholder="Search item name or code"
                    wire:model.live.debounce.500ms="search" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:select size="sm" class="w-full sm:w-48" wire:model.live="status" aria-label="Status">
                    <flux:select.option value="">All statuses</flux:select.option>
                    <flux:select.option value="attention">Needs attention</flux:select.option>
                    @foreach (StockStatus::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" class="w-full sm:w-48" wire:model.live="category" aria-label="Category">
                    <flux:select.option value="">All categories</flux:select.option>
                    @foreach ($this->categories as $cat)
                        <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" class="w-full sm:w-48" wire:model.live="sort" aria-label="Sort">
                    @foreach (\App\Livewire\Monitoring\StockMonitoring::SORTS as $key => $label)
                        <flux:select.option value="{{ $key }}">Sort: {{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($filtered)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters" tooltip="Clear filters" />
                @endif
            </div>
        </div>

        <div wire:loading.class="opacity-60" wire:target="search,status,category,sort,filterStatus,clearFilters,gotoPage,nextPage,previousPage">
            @if ($this->items->isNotEmpty())
                <flux:table :paginate="$this->items">
                    <flux:table.columns>
                        <flux:table.column class="w-8"><span class="sr-only">Expand</span></flux:table.column>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column class="min-w-40">Usable stock</flux:table.column>
                        <flux:table.column align="end">Committed</flux:table.column>
                        <flux:table.column align="end">Free</flux:table.column>
                        <flux:table.column align="end">Usage / day</flux:table.column>
                        <flux:table.column align="end">Days of cover</flux:table.column>
                        <flux:table.column>Next expiry</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->items as $item)
                            @php
                                $st = $item->stock_status;
                                // Bar scale: the reorder level sits at the midpoint.
                                $scale = max(1, $item->reorder_level * 2);
                                $fill = min(100, $item->available / $scale * 100);
                                $isOpen = $expanded === $item->id;
                            @endphp

                            <flux:table.row wire:key="item-{{ $item->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-white/5"
                                wire:click="toggle({{ $item->id }})">
                                <flux:table.cell>
                                    <span class="inline-flex transition-transform {{ $isOpen ? 'rotate-90' : '' }}" aria-hidden="true">
                                        <flux:icon.chevron-right variant="micro" class="text-zinc-400" />
                                    </span>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <button type="button" class="text-left" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                        aria-controls="detail-{{ $item->id }}">
                                        <div class="font-medium text-zinc-800 dark:text-white">{{ $item->name }}</div>
                                        @php $cats = $item->inventoryCategory->pluck('name')->join(', '); @endphp
                                        <flux:text size="sm" class="max-w-56 truncate" title="{{ $cats }}">
                                            {{ $item->item_code }}{{ $cats !== '' ? ' · '.$cats : '' }}
                                        </flux:text>
                                    </button>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$st->color()">{{ $st->label() }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="flex items-baseline justify-between gap-2 tabular-nums">
                                        <span class="font-medium text-zinc-800 dark:text-white">{{ number_format($item->available) }}
                                            <span class="font-normal text-zinc-400">{{ $item->unit }}</span></span>
                                        <span class="text-xs text-zinc-400">reorder {{ number_format($item->reorder_level) }}</span>
                                    </div>
                                    <div class="relative mt-1.5 h-1.5 rounded-full bg-zinc-100 dark:bg-white/10"
                                        title="Minimum {{ $item->minimum_stock }} · Reorder level {{ $item->reorder_level }}">
                                        <div class="h-full rounded-full {{ $barColor[$st->color()] }}" style="width: {{ $fill }}%"></div>
                                        {{-- Reorder-level marker --}}
                                        <div class="absolute -top-0.5 h-2.5 w-px bg-zinc-400 dark:bg-zinc-500" style="left: 50%"></div>
                                    </div>
                                    @if ($item->expired > 0)
                                        <div class="mt-1 text-xs text-red-600 dark:text-red-400">+{{ number_format($item->expired) }} expired on hand</div>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    {{ $item->committed ? number_format($item->committed) : '—' }}
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums font-medium">{{ number_format($item->free) }}</flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    {{ $item->daily_usage > 0 ? number_format($item->daily_usage, $item->daily_usage < 10 ? 1 : 0) : '—' }}
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    @if ($item->cover_days === null)
                                        <span class="text-zinc-400" title="No issuances in the last {{ \App\Livewire\Monitoring\StockMonitoring::USAGE_DAYS }} days">No usage</span>
                                    @else
                                        <span @class([
                                            'font-medium',
                                            'text-red-600 dark:text-red-400' => $item->cover_days <= 7,
                                            'text-amber-600 dark:text-amber-400' => $item->cover_days > 7 && $item->cover_days <= 30,
                                        ])>{{ number_format($item->cover_days) }} {{ Str::plural('day', $item->cover_days) }}</span>
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($item->next_expiry)
                                        @php $exp = \Illuminate\Support\Carbon::parse($item->next_expiry); @endphp
                                        <span @class(['text-amber-600 dark:text-amber-400' => $exp->lte(today()->addDays(30))])>{{ $exp->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>

                            @if ($isOpen && ($detail = $this->detail))
                                <flux:table.row wire:key="detail-{{ $item->id }}" id="detail-{{ $item->id }}">
                                    <flux:table.cell colspan="9" class="bg-zinc-50 p-0! dark:bg-white/[0.03]">
                                        @include('livewire.monitoring.partials.stock-detail', ['detail' => $detail])
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                    <flux:icon name="cube" variant="outline" class="size-8 text-zinc-400" />
                    <flux:heading size="sm">No items found</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ $filtered ? 'Try a different search or filter.' : 'Active inventory items will appear here.' }}
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>
</div>
