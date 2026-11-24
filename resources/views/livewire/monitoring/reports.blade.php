@php
    [$start, $end] = $this->period;
    $summary = $this->summary;
    $health = $this->stockHealth;
    $movement = $this->movement;
    $bucket = $this->bucket;

    // Literal class strings so Tailwind compiles them (enum colors => chart classes).
    $tone = [
        'green' => 'text-emerald-500 dark:text-emerald-400',
        'emerald' => 'text-emerald-500 dark:text-emerald-400',
        'sky' => 'text-sky-500 dark:text-sky-400',
        'amber' => 'text-amber-500 dark:text-amber-400',
        'orange' => 'text-orange-500 dark:text-orange-400',
        'red' => 'text-red-500 dark:text-red-400',
        'violet' => 'text-violet-500 dark:text-violet-400',
        'zinc' => 'text-zinc-400 dark:text-zinc-500',
    ];

    $axisFormat = match ($bucket) {
        'day', 'week' => ['month' => 'short', 'day' => 'numeric'],
        default => ['month' => 'short', 'year' => '2-digit'],
    };
    $tooltipFormat = match ($bucket) {
        'day' => ['dateStyle' => 'medium'],
        'week' => ['month' => 'short', 'day' => 'numeric', 'year' => 'numeric'],
        default => ['month' => 'long', 'year' => 'numeric'],
    };

    $healthRows = [
        ['label' => 'Out of stock', 'value' => $health['out'], 'color' => $tone['red']],
        ['label' => 'Critical', 'value' => $health['critical'], 'color' => $tone['orange']],
        ['label' => 'Reorder', 'value' => $health['reorder'], 'color' => $tone['amber']],
        ['label' => 'Healthy', 'value' => $health['healthy'], 'color' => $tone['green']],
    ];
    $activeItems = array_sum($health);

    $hasMovement = collect($movement)->contains(fn ($r) => $r['received'] || $r['issued'] || $r['written_down']);

    $deltaBadge = function (?float $delta, bool $upIsGood = true) {
        if ($delta === null) {
            return null;
        }
        $good = $delta == 0 ? null : (($delta > 0) === $upIsGood);

        return [
            'text' => ($delta > 0 ? '+' : '').rtrim(rtrim(number_format($delta, 1), '0'), '.').'%',
            'color' => $good === null ? 'zinc' : ($good ? 'green' : 'red'),
            'icon' => $delta > 0 ? 'arrow-trending-up' : ($delta < 0 ? 'arrow-trending-down' : 'minus'),
        ];
    };
@endphp

<div class="flex flex-col gap-6">

    {{-- Header + period filter --}}
    <flux:card variant="default">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Reports</flux:heading>
                <flux:text class="mt-1">
                    Stock levels, movement and activity for
                    <span class="font-medium text-zinc-800 dark:text-white">{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</span>.
                </flux:text>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if ($range === 'custom')
                    <x-date-picker size="sm" class="sm:w-40" wire:model.live="from" aria-label="From" align="end" />
                    <flux:text size="sm" class="hidden text-zinc-500 sm:block dark:text-zinc-400">to</flux:text>
                    <x-date-picker size="sm" class="sm:w-40" wire:model.live="to" aria-label="To" align="end" />
                @endif

                <flux:select size="sm" class="sm:w-44" wire:model.live="range" aria-label="Period">
                    @foreach (\App\Livewire\Monitoring\Reports::RANGES as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    <div class="flex flex-col gap-6" wire:loading.class="opacity-60 transition-opacity">

        {{-- Summary tiles --}}
        @php
            $tiles = [
                [
                    'label' => 'Stock value on hand',
                    'value' => number_format($summary['stock_value'], 2),
                    'note' => number_format($summary['stock_units']).' usable units'
                        .($summary['expired_value'] > 0 ? ' · '.number_format($summary['expired_value'], 2).' expired' : ''),
                    'icon' => 'banknotes',
                ],
                [
                    'label' => 'Units received',
                    'value' => number_format($summary['received']),
                    'badge' => $deltaBadge($summary['received_delta']),
                    'note' => 'vs previous period',
                    'icon' => 'arrow-down-tray',
                ],
                [
                    'label' => 'Units issued',
                    'value' => number_format($summary['issued']),
                    'badge' => $deltaBadge($summary['issued_delta']),
                    'note' => 'vs previous period',
                    'icon' => 'arrow-up-tray',
                ],
                [
                    'label' => 'Net adjustments',
                    'value' => ($summary['adjusted'] > 0 ? '+' : '').number_format($summary['adjusted']),
                    'note' => 'units · previous '.($summary['adjusted_previous'] > 0 ? '+' : '').number_format($summary['adjusted_previous']),
                    'icon' => 'adjustments-horizontal',
                    'color' => $summary['adjusted'] < 0 ? 'red' : null,
                ],
                [
                    'label' => 'Items needing reorder',
                    'value' => number_format($summary['needs_reorder']),
                    'note' => number_format($summary['out_of_stock']).' out of stock',
                    'icon' => 'shopping-cart',
                    'color' => $summary['needs_reorder'] > 0 ? 'amber' : null,
                ],
                [
                    'label' => 'Expiring in 30 days',
                    'value' => number_format($summary['expiring_soon']),
                    'note' => 'batches with stock',
                    'icon' => 'clock',
                    'color' => $summary['expiring_soon'] > 0 ? 'amber' : null,
                ],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($tiles as $tile)
                <flux:card size="sm" class="flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <flux:text size="sm">{{ $tile['label'] }}</flux:text>
                        <flux:icon :name="$tile['icon']" variant="micro" class="text-zinc-400" />
                    </div>

                    <div class="flex items-baseline gap-2">
                        <flux:heading size="xl" @class([
                            'tabular-nums',
                            'text-red-600! dark:text-red-400!' => ($tile['color'] ?? null) === 'red',
                            'text-amber-600! dark:text-amber-400!' => ($tile['color'] ?? null) === 'amber',
                        ])>{{ $tile['value'] }}</flux:heading>

                        @if ($badge = $tile['badge'] ?? null)
                            <flux:badge size="sm" :color="$badge['color']" :icon="$badge['icon']">{{ $badge['text'] }}</flux:badge>
                        @endif
                    </div>

                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $tile['note'] }}</flux:text>
                </flux:card>
            @endforeach
        </div>

        {{-- Movement + stock health --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <flux:card class="lg:col-span-2">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <flux:heading>Stock movement</flux:heading>
                        <flux:text size="sm">Units received, issued and written down per {{ $bucket }}</flux:text>
                    </div>
                </div>

                @if ($hasMovement)
                    <x-chart :value="$movement" class="h-72" wire:key="movement-{{ $bucket }}">
                        <x-chart.area field="received" class="{{ $tone['emerald'] }}" />
                        <x-chart.line field="received" label="Received" class="{{ $tone['emerald'] }}" />
                        <x-chart.line field="issued" label="Issued" class="{{ $tone['sky'] }}" />
                        <x-chart.line field="written_down" label="Written down" class="{{ $tone['red'] }}" dashed />
                        <x-chart.axis axis="x" field="date" :format="$axisFormat" />
                        <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                        <x-chart.cursor />
                        <x-chart.tooltip :format="$tooltipFormat" />
                        <x-chart.legend />
                    </x-chart>
                @else
                    <x-reports.empty icon="arrows-right-left" message="No stock movement in this period." class="h-72" />
                @endif
            </flux:card>

            <flux:card>
                <flux:heading>Stock health</flux:heading>
                <flux:text size="sm" class="mb-4">Active items by usable stock vs. thresholds</flux:text>

                @if ($activeItems > 0)
                    <x-chart :value="$healthRows" class="h-72">
                        <x-chart.donut :colors="array_column($healthRows, 'color')">
                            <flux:heading size="xl" class="tabular-nums">{{ number_format($activeItems) }}</flux:heading>
                            <flux:text size="sm">active items</flux:text>
                        </x-chart.donut>
                        <x-chart.tooltip />
                        <x-chart.legend values />
                    </x-chart>
                @else
                    <x-reports.empty icon="cube" message="No active items yet." class="h-72" />
                @endif
            </flux:card>
        </div>

        {{-- Receiving + value by location --}}
        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading>Receiving by supplier</flux:heading>
                <flux:text size="sm" class="mb-4">Value of stock received in this period</flux:text>

                @php $suppliers = $this->receivingBySupplier; @endphp
                @if ($suppliers->isNotEmpty())
                    <x-chart :value="$suppliers" horizontal style="height: {{ max(160, $suppliers->count() * 44 + 40) }}px">
                        <x-chart.bar field="value" label="Value" class="{{ $tone['emerald'] }}" />
                        <x-chart.axis axis="y" field="supplier" />
                        <x-chart.axis axis="x" :format="['notation' => 'compact']" />
                        <x-chart.cursor />
                        <x-chart.tooltip :value-format="['maximumFractionDigits' => 2]" />
                    </x-chart>
                @else
                    <x-reports.empty icon="truck" message="No deliveries received in this period." class="h-40" />
                @endif
            </flux:card>

            <flux:card>
                <flux:heading>Stock value by location</flux:heading>
                <flux:text size="sm" class="mb-4">Usable stock currently on hand</flux:text>

                @php $locations = $this->stockByLocation; @endphp
                @if ($locations->isNotEmpty())
                    <x-chart :value="$locations" horizontal style="height: {{ max(160, $locations->count() * 44 + 40) }}px">
                        <x-chart.bar field="value" label="Value" class="{{ $tone['violet'] }}" />
                        <x-chart.axis axis="y" field="location" />
                        <x-chart.axis axis="x" :format="['notation' => 'compact']" />
                        <x-chart.cursor />
                        <x-chart.tooltip :value-format="['maximumFractionDigits' => 2]" />
                    </x-chart>
                @else
                    <x-reports.empty icon="map-pin" message="No usable stock on hand." class="h-40" />
                @endif
            </flux:card>
        </div>

        {{-- Issued items + adjustments + requisitions --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <flux:card>
                <flux:heading>Top issued items</flux:heading>
                <flux:text size="sm" class="mb-4">Most units released in this period</flux:text>

                @php $issued = $this->topIssued; @endphp
                @if ($issued->isNotEmpty())
                    <x-chart :value="$issued" horizontal style="height: {{ max(160, $issued->count() * 36 + 40) }}px">
                        <x-chart.bar field="units" label="Issued" class="{{ $tone['sky'] }}" />
                        <x-chart.axis axis="y" field="item" />
                        <x-chart.axis axis="x" />
                        <x-chart.cursor />
                        <x-chart.tooltip />
                    </x-chart>
                @else
                    <x-reports.empty icon="arrow-up-tray" message="Nothing issued in this period." class="h-64" />
                @endif
            </flux:card>

            <flux:card>
                <flux:heading>Adjustments by type</flux:heading>
                <flux:text size="sm" class="mb-4">Number of adjustments recorded</flux:text>

                @php $adjustments = $this->adjustmentsByType; @endphp
                @if ($adjustments->isNotEmpty())
                    <x-chart :value="$adjustments->map(fn ($r) => ['label' => $r['label'], 'value' => $r['count']])" class="h-64">
                        <x-chart.pie :colors="$adjustments->map(fn ($r) => $tone[$r['type']->color()] ?? $tone['zinc'])->all()" />
                        <x-chart.tooltip />
                        <x-chart.legend />
                    </x-chart>

                    <div class="mt-4 space-y-1">
                        @foreach ($adjustments as $row)
                            <div class="flex items-center justify-between text-sm">
                                <flux:text size="sm">{{ $row['label'] }}</flux:text>
                                <flux:text size="sm" variant="strong" class="tabular-nums">
                                    {{ $row['units'] > 0 ? '+' : '' }}{{ number_format($row['units']) }} units
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-reports.empty icon="adjustments-horizontal" message="No adjustments in this period." class="h-64" />
                @endif
            </flux:card>

            <flux:card>
                <flux:heading>Requisitions by status</flux:heading>
                <flux:text size="sm" class="mb-4">Requests submitted in this period</flux:text>

                @php $requisitions = $this->requisitionsByStatus; @endphp
                @if ($requisitions->isNotEmpty())
                    <x-chart :value="$requisitions->map(fn ($r) => ['label' => $r['label'], 'value' => $r['count']])" class="h-64">
                        <x-chart.donut :colors="$requisitions->map(fn ($r) => $tone[$r['status']->color()] ?? $tone['zinc'])->all()">
                            <flux:heading size="xl" class="tabular-nums">{{ number_format($requisitions->sum('count')) }}</flux:heading>
                            <flux:text size="sm">requests</flux:text>
                        </x-chart.donut>
                        <x-chart.tooltip />
                        <x-chart.legend />
                    </x-chart>
                @else
                    <x-reports.empty icon="document-text" message="No requests submitted in this period." class="h-64" />
                @endif
            </flux:card>
        </div>

        {{-- Reorder list --}}
        <flux:card>
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <flux:heading>Items needing reorder</flux:heading>
                    <flux:text size="sm">Active items at or below their reorder level (current stock, not limited to the period)</flux:text>
                </div>
                <flux:badge size="sm" :color="$this->reorderItems->isEmpty() ? 'green' : 'amber'">
                    {{ $this->reorderItems->count() }} {{ Str::plural('item', $this->reorderItems->count()) }}
                </flux:badge>
            </div>

            @if ($this->reorderItems->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end">Usable stock</flux:table.column>
                        <flux:table.column align="end">Minimum</flux:table.column>
                        <flux:table.column align="end">Reorder level</flux:table.column>
                        <flux:table.column align="end">Shortfall</flux:table.column>
                        <flux:table.column>Last received</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->reorderItems as $item)
                            @php
                                [$label, $color] = match ($item->health) {
                                    'out' => ['Out of stock', 'red'],
                                    'critical' => ['Critical', 'orange'],
                                    default => ['Reorder', 'amber'],
                                };
                            @endphp
                            <flux:table.row wire:key="reorder-{{ $item->id }}">
                                <flux:table.cell>
                                    <div class="font-medium text-zinc-800 dark:text-white">{{ $item->name }}</div>
                                    <flux:text size="sm">{{ $item->item_code }}</flux:text>
                                </flux:table.cell>
                                <flux:table.cell><flux:badge size="sm" :color="$color">{{ $label }}</flux:badge></flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">
                                    {{ number_format((int) $item->available_qty) }} <span class="text-zinc-400">{{ $item->unit }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($item->minimum_stock) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums">{{ number_format($item->reorder_level) }}</flux:table.cell>
                                <flux:table.cell align="end" class="tabular-nums font-medium">{{ number_format($item->shortfall) }}</flux:table.cell>
                                <flux:table.cell>
                                    {{ $item->last_received_at ? \Illuminate\Support\Carbon::parse($item->last_received_at)->format('M d, Y') : 'Never' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <x-reports.empty icon="check-circle" message="Every active item is above its reorder level." class="h-32" />
            @endif
        </flux:card>
    </div>
</div>
