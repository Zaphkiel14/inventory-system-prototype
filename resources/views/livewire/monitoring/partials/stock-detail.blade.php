{{-- Drill-down for one item on the Stock Monitoring page. Expects $detail from StockMonitoring::detail(). --}}
@php
    $item = $detail['item'];
    $movement = $detail['movement'];
    $days = \App\Livewire\Monitoring\StockMonitoring::TREND_DAYS;
    $hasTrend = collect($detail['trend'])->contains(fn ($r) => $r['balance'] > 0);
@endphp

<div class="grid gap-6 p-4 2xl:grid-cols-5">

    {{-- Balance trend + movement --}}
    <div class="flex flex-col gap-4 2xl:col-span-2">
        <div>
            <flux:heading size="sm">On-hand balance · last {{ $days }} days</flux:heading>
            <flux:text size="sm">Includes expired stock still on the shelf. The dashed line is the reorder level.</flux:text>
        </div>

        @if ($hasTrend)
            @php
                $trend = array_map(fn ($r) => [...$r, 'reorder' => $item->reorder_level], $detail['trend']);
            @endphp
            <x-chart :value="$trend" class="h-44" wire:key="trend-{{ $item->id }}">
                <x-chart.area field="balance" class="text-sky-500 dark:text-sky-400" />
                <x-chart.line field="balance" label="On hand" curve="linear" class="text-sky-500 dark:text-sky-400" />
                <x-chart.line field="reorder" label="Reorder level" curve="linear" :width="1" dashed class="text-amber-500 dark:text-amber-400" />
                <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
                <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                <x-chart.cursor />
                <x-chart.tooltip :format="['dateStyle' => 'medium']" />
            </x-chart>
        @else
            <x-reports.empty icon="chart-bar" message="No stock on hand in this window." class="h-44" />
        @endif

        <div class="grid grid-cols-3 gap-3">
            @foreach ([
                ['Received', $movement['received'], '+'],
                ['Issued', $movement['issued'], '−'],
                ['Adjusted', $movement['adjusted'], $movement['adjusted'] > 0 ? '+' : '−'],
            ] as [$label, $value, $sign])
                <div class="rounded-lg border border-zinc-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                    <flux:text size="sm">{{ $label }}</flux:text>
                    <div class="font-semibold tabular-nums text-zinc-800 dark:text-white">
                        {{ $value ? $sign : '' }}{{ number_format(abs($value)) }}
                    </div>
                </div>
            @endforeach
        </div>

        <flux:text size="sm">
            Minimum {{ number_format($item->minimum_stock) }} ·
            Reorder level {{ number_format($item->reorder_level) }} ·
            Stock value {{ number_format($item->value, 2) }} ·
            Last received {{ $item->last_received_at ? \Illuminate\Support\Carbon::parse($item->last_received_at)->format('M d, Y') : 'never' }}
        </flux:text>
    </div>

    {{-- Batches in issue order --}}
    <div class="flex flex-col gap-3 2xl:col-span-3">
        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="sm">Batches in issue order</flux:heading>
                <flux:text size="sm">
                    {{ $detail['method'] === 'FEFO' ? 'First expiring, first out' : 'First in, first out' }}.
                    @if ($item->committed)
                        {{ number_format($item->committed) }} {{ $item->unit }} already promised to approved requests.
                    @endif
                </flux:text>
            </div>
            <flux:badge size="sm" color="zinc">{{ $detail['method'] }}</flux:badge>
        </div>

        @if (count($detail['batches']))
            <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-white/10 dark:bg-white/5">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs text-zinc-500 dark:border-white/10 dark:text-zinc-400">
                            <th class="px-3 py-2 font-medium">#</th>
                            <th class="px-3 py-2 font-medium">Batch / Lot</th>
                            <th class="px-3 py-2 font-medium">Location / Received</th>
                            <th class="px-3 py-2 text-right font-medium">Remaining</th>
                            <th class="px-3 py-2 text-right font-medium">Reserved</th>
                            <th class="px-3 py-2 font-medium">Expires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-white/5">
                        @foreach ($detail['batches'] as $row)
                            @php
                                $batch = $row['batch'];
                                $soon = ! $row['expired'] && $batch->expiration_date?->lte(today()->addDays(30));
                            @endphp
                            <tr wire:key="batch-{{ $batch->id }}" @class(['opacity-70' => $row['expired']])>
                                <td class="px-3 py-2">
                                    @if ($row['expired'])
                                        <flux:badge size="sm" color="red">Expired</flux:badge>
                                    @elseif ($row['order'] === 1)
                                        <flux:badge size="sm" color="emerald">Next out</flux:badge>
                                    @else
                                        <span class="tabular-nums text-zinc-500 dark:text-zinc-400">{{ $row['order'] }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-zinc-800 dark:text-white">{{ $batch->batch_number ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $batch->lot_number ?? '—' }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="text-zinc-600 dark:text-zinc-300">{{ $batch->storageLocation?->name ?? '—' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $batch->received_at?->format('M d, Y') }}</div>
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums text-zinc-800 dark:text-white">
                                    {{ number_format($batch->remaining_qty) }}
                                    <span class="text-xs text-zinc-400">/ {{ number_format($batch->received_qty) }}</span>
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($row['reserved'])
                                        <span class="font-medium text-sky-600 dark:text-sky-400">{{ number_format($row['reserved']) }}</span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td @class([
                                    'whitespace-nowrap px-3 py-2',
                                    'text-red-600 dark:text-red-400' => $row['expired'],
                                    'text-amber-600 dark:text-amber-400' => $soon,
                                    'text-zinc-600 dark:text-zinc-300' => ! $row['expired'] && ! $soon,
                                ])>{{ $batch->expiration_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (collect($detail['batches'])->contains('expired', true))
                <flux:text size="sm">
                    Expired batches are never issued.
                    <flux:link :href="route('monitoring.expiration-monitoring', ['status' => 'expired'])" wire:navigate>Write them off</flux:link>
                    on the Expiration Monitoring page.
                </flux:text>
            @endif
        @else
            <x-reports.empty icon="archive-box-x-mark" message="No batches with stock left." class="h-32" />
        @endif
    </div>
</div>
