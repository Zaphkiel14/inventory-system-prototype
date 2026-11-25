<div class="flex flex-col gap-6">

    {{-- Header + quick actions (renders immediately, no queries) --}}
    <flux:card variant="default">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Dashboard</flux:heading>
                <flux:text class="mt-1">
                    Welcome back, {{ auth()->user()->name }} · {{ now()->format('l, M j, Y') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button size="sm" icon="arrow-down-tray" :href="route('inventory.receiving')" wire:navigate>New receipt</flux:button>
                <flux:button size="sm" icon="adjustments-horizontal" :href="route('inventory.adjustments')" wire:navigate>New adjustment</flux:button>
                <flux:button size="sm" icon="clipboard-document-check" :href="route('requisitions.approvals')" wire:navigate>Review approvals</flux:button>
                <flux:button size="sm" variant="primary" icon="arrow-up-tray" :href="route('requisitions.issuances')" wire:navigate>Issue stock</flux:button>
            </div>
        </div>
    </flux:card>

    <div class="flex flex-col gap-6" wire:loading.class="opacity-60 transition-opacity">

        {{-- Needs action --}}
        @island(name: 'needs-action', defer: true)
            @placeholder
                <div>
                    <x-skeleton.stat-tiles :count="4" />
                </div>
            @endplaceholder

            @php
                // Literal class strings so Tailwind compiles them.
                $toneText = [
                    'red' => 'text-red-600 dark:text-red-400',
                    'amber' => 'text-amber-600 dark:text-amber-400',
                    'sky' => 'text-sky-600 dark:text-sky-400',
                ];
            @endphp

            <div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($this->tiles as $tile)
                        @php $hot = $tile['value'] > 0; $text = $toneText[$tile['tone']] ?? $toneText['amber']; @endphp
                        <a href="{{ $tile['href'] }}" wire:navigate wire:key="tile-{{ $loop->index }}"
                            class="rounded-xl transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                            <flux:card size="sm" class="flex h-full flex-col gap-2 hover:bg-zinc-50 dark:hover:bg-white/5">
                                <div class="flex items-center justify-between gap-2">
                                    <flux:text size="sm">{{ $tile['label'] }}</flux:text>
                                    <flux:icon :name="$tile['icon']" variant="micro" @class([$text => $hot, 'text-zinc-400' => ! $hot]) />
                                </div>

                                <flux:heading size="xl" @class(['tabular-nums', "$text!" => $hot])>
                                    {{ number_format($tile['value']) }}
                                </flux:heading>

                                <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $tile['note'] }}</flux:text>
                            </flux:card>
                        </a>
                    @endforeach
                </div>
            </div>
        @endisland

        {{-- Requisition queue + stock health --}}
        @island(name: 'queue-health', defer: true)
            @placeholder
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-skeleton.table :rows="5" :columns="5" class="lg:col-span-2"
                        heading="Requisition queue" description="Oldest requests waiting for approval or release" />
                    <x-skeleton.chart height="h-64" donut :action="false"
                        heading="Stock health" description="Active items by usable stock vs. thresholds" />
                </div>
            @endplaceholder

            @php
                // Literal class strings so Tailwind compiles them (tone keys => chart classes).
                $tone = [
                    'green' => 'text-emerald-500 dark:text-emerald-400',
                    'amber' => 'text-amber-500 dark:text-amber-400',
                    'orange' => 'text-orange-500 dark:text-orange-400',
                    'red' => 'text-red-500 dark:text-red-400',
                ];
                $healthRows = $this->healthRows;
            @endphp

            <div class="grid gap-4 lg:grid-cols-3">
                <flux:card class="lg:col-span-2">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>Requisition queue</flux:heading>
                            <flux:text size="sm">Oldest requests waiting for approval or release</flux:text>
                        </div>
                        <flux:link size="sm" :href="route('requisitions.approvals')" wire:navigate class="shrink-0 text-sm">View all</flux:link>
                    </div>

                    @if ($this->queue->isNotEmpty())
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Request</flux:table.column>
                                <flux:table.column>Requester</flux:table.column>
                                <flux:table.column align="end">Items</flux:table.column>
                                <flux:table.column>Waiting</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->queue as $requisition)
                                    <flux:table.row wire:key="queue-{{ $requisition->id }}">
                                        <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $requisition->request_number }}</flux:table.cell>
                                        <flux:table.cell>{{ $requisition->requester_name }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ $requisition->items_count }}</flux:table.cell>
                                        <flux:table.cell>{{ $requisition->submitted_at?->diffForHumans(short: true) ?? '—' }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge size="sm" :color="$requisition->status->color()">{{ $requisition->status->label() }}</flux:badge>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>

                        @if ($this->queueTotal > $this->queue->count())
                            <flux:text size="sm" class="mt-3 text-zinc-500 dark:text-zinc-400">
                                + {{ number_format($this->queueTotal - $this->queue->count()) }} more waiting
                            </flux:text>
                        @endif
                    @else
                        <x-reports.empty icon="check-circle" message="No requests waiting. All caught up." class="h-48" />
                    @endif
                </flux:card>

                <flux:card>
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>Stock health</flux:heading>
                            <flux:text size="sm">Active items by usable stock vs. thresholds</flux:text>
                        </div>
                    </div>

                    @if ($this->activeItems > 0)
                        <x-chart :value="$healthRows" class="h-64">
                            <x-chart.donut :colors="array_map(fn ($row) => $tone[$row['tone']] ?? $tone['green'], $healthRows)">
                                <flux:heading size="xl" class="tabular-nums">{{ number_format($this->activeItems) }}</flux:heading>
                                <flux:text size="sm">active items</flux:text>
                            </x-chart.donut>
                            <x-chart.tooltip />
                            <x-chart.legend values />
                        </x-chart>
                    @else
                        <x-reports.empty icon="cube" message="No active items yet." class="h-64" />
                    @endif
                </flux:card>
            </div>
        @endisland

        {{-- Low stock + expiring soon --}}
        @island(name: 'lists', lazy: true)
            @placeholder
                <div class="grid gap-4 lg:grid-cols-2">
                    <x-skeleton.list-card :rows="5" heading="Low stock"
                        description="Items at or below their reorder level, most urgent first" />
                    <x-skeleton.list-card :rows="5" heading="Expiring soon"
                        :description="'Batches with stock expiring in the next '.\App\Livewire\Dashboard::EXPIRY_DAYS.' days'" />
                </div>
            @endplaceholder

            <div class="grid gap-4 lg:grid-cols-2">
                <flux:card>
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>Low stock</flux:heading>
                            <flux:text size="sm">Items at or below their reorder level, most urgent first</flux:text>
                        </div>
                        <flux:link size="sm" :href="route('monitoring.stock-monitoring')" wire:navigate class="shrink-0 text-sm">View all</flux:link>
                    </div>

                    @php $lowStock = $this->lowStock->take(\App\Livewire\Dashboard::LIST_LIMIT); @endphp
                    @if ($lowStock->isNotEmpty())
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Item</flux:table.column>
                                <flux:table.column align="end">Usable</flux:table.column>
                                <flux:table.column align="end">Reorder at</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($lowStock as $item)
                                    @php $status = \App\Enums\StockStatus::from($item->health); @endphp
                                    <flux:table.row wire:key="low-{{ $item->id }}">
                                        <flux:table.cell>
                                            <div class="font-medium text-zinc-800 dark:text-white">{{ $item->name }}</div>
                                            <flux:text size="sm">{{ $item->item_code }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">
                                            {{ number_format((int) $item->available_qty) }} <span class="text-zinc-400">{{ $item->unit }}</span>
                                        </flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">{{ number_format($item->reorder_level) }}</flux:table.cell>
                                        <flux:table.cell><flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge></flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <x-reports.empty icon="check-circle" message="Every active item is above its reorder level." class="h-40" />
                    @endif
                </flux:card>

                <flux:card>
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>Expiring soon</flux:heading>
                            <flux:text size="sm">Batches with stock expiring in the next {{ \App\Livewire\Dashboard::EXPIRY_DAYS }} days</flux:text>
                        </div>
                        <flux:link size="sm" :href="route('monitoring.expiration-monitoring')" wire:navigate class="shrink-0 text-sm">View all</flux:link>
                    </div>

                    @if ($this->expiringSoon->isNotEmpty())
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Item</flux:table.column>
                                <flux:table.column>Batch</flux:table.column>
                                <flux:table.column align="end">Qty</flux:table.column>
                                <flux:table.column>Expires</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->expiringSoon as $batch)
                                    @php $days = (int) today()->diffInDays($batch->expiration_date); @endphp
                                    <flux:table.row wire:key="exp-{{ $batch->id }}">
                                        <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $batch->inventoryItem?->name }}</flux:table.cell>
                                        <flux:table.cell>{{ $batch->batch_number }}</flux:table.cell>
                                        <flux:table.cell align="end" class="tabular-nums">
                                            {{ number_format($batch->remaining_qty) }} <span class="text-zinc-400">{{ $batch->inventoryItem?->unit }}</span>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <div class="flex items-center gap-2">
                                                <span class="tabular-nums">{{ $batch->expiration_date->format('M d') }}</span>
                                                <flux:badge size="sm" :color="$days <= 7 ? 'orange' : 'amber'">
                                                    {{ $days === 0 ? 'today' : $days.'d' }}
                                                </flux:badge>
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <x-reports.empty icon="calendar-days" message="Nothing expires in the next {{ \App\Livewire\Dashboard::EXPIRY_DAYS }} days." class="h-40" />
                    @endif
                </flux:card>
            </div>
        @endisland

        {{-- Stock movement --}}
        @island(name: 'movement', lazy: true)
            @placeholder
                <div>
                    {{-- Description carries live totals, so it stays a skeleton line. --}}
                    <x-skeleton.chart height="h-64" heading="Stock movement" />
                </div>
            @endplaceholder

            @php
                // Literal class strings so Tailwind compiles them.
                $tone = [
                    'emerald' => 'text-emerald-500 dark:text-emerald-400',
                    'sky' => 'text-sky-500 dark:text-sky-400',
                    'red' => 'text-red-500 dark:text-red-400',
                ];
                $onHand = $this->onHand;
            @endphp

            <div>
                <flux:card>
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <flux:heading>Stock movement</flux:heading>
                            <flux:text size="sm">
                                Units received, issued and written down over the last {{ \App\Livewire\Dashboard::MOVEMENT_DAYS }} days ·
                                <span class="font-medium text-zinc-800 dark:text-white">{{ number_format($onHand['value'], 2) }}</span>
                                usable stock value ({{ number_format($onHand['units']) }} units)
                            </flux:text>
                        </div>
                        <flux:link size="sm" :href="route('monitoring.reports')" wire:navigate class="shrink-0 text-sm">Full report</flux:link>
                    </div>

                    @if ($this->hasMovement)
                        <x-chart :value="$this->movement" class="h-64">
                            <x-chart.area field="received" class="{{ $tone['emerald'] }}" />
                            <x-chart.line field="received" label="Received" class="{{ $tone['emerald'] }}" />
                            <x-chart.line field="issued" label="Issued" class="{{ $tone['sky'] }}" />
                            <x-chart.line field="written_down" label="Written down" class="{{ $tone['red'] }}" dashed />
                            <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
                            <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                            <x-chart.cursor />
                            <x-chart.tooltip :format="['dateStyle' => 'medium']" />
                            <x-chart.legend />
                        </x-chart>
                    @else
                        <x-reports.empty icon="arrows-right-left" message="No stock movement in the last {{ \App\Livewire\Dashboard::MOVEMENT_DAYS }} days." class="h-64" />
                    @endif
                </flux:card>
            </div>
        @endisland

        {{-- Recent activity --}}
        @island(name: 'activity', lazy: true)
            @placeholder
                <div>
                    <x-skeleton.table :rows="5" :columns="6" heading="Recent activity"
                        description="Latest entries in the stock ledger" />
                </div>
            @endplaceholder

            <div>
                <flux:card>
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <flux:heading>Recent activity</flux:heading>
                            <flux:text size="sm">Latest entries in the stock ledger</flux:text>
                        </div>
                        <flux:link size="sm" :href="route('monitoring.transaction-history')" wire:navigate class="shrink-0 text-sm">View all</flux:link>
                    </div>

                    @if ($this->recentActivity->isNotEmpty())
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>When</flux:table.column>
                                <flux:table.column>Type</flux:table.column>
                                <flux:table.column>Item</flux:table.column>
                                <flux:table.column align="end">Qty</flux:table.column>
                                <flux:table.column>Reference</flux:table.column>
                                <flux:table.column>By</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($this->recentActivity as $entry)
                                    <flux:table.row wire:key="activity-{{ $entry->id }}">
                                        <flux:table.cell class="whitespace-nowrap">{{ $entry->created_at->diffForHumans(short: true) }}</flux:table.cell>
                                        <flux:table.cell>
                                            <flux:badge size="sm" :color="$entry->type->color()" :icon="$entry->type->icon()">{{ $entry->type->label() }}</flux:badge>
                                        </flux:table.cell>
                                        <flux:table.cell class="font-medium text-zinc-800 dark:text-white">{{ $entry->inventoryItem?->name ?? '—' }}</flux:table.cell>
                                        <flux:table.cell align="end" @class([
                                            'tabular-nums font-medium',
                                            'text-emerald-600 dark:text-emerald-400' => $entry->quantity > 0,
                                            'text-red-600 dark:text-red-400' => $entry->quantity < 0,
                                        ])>
                                            {{ $entry->quantity > 0 ? '+' : '' }}{{ number_format($entry->quantity) }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if ($entry->reference)
                                                <div>{{ $entry->reference['label'] }}</div>
                                                @if (filled($entry->reference['detail']))
                                                    <flux:text size="sm">{{ $entry->reference['detail'] }}</flux:text>
                                                @endif
                                            @else
                                                <span class="text-zinc-400">—</span>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $entry->user?->name ?? '—' }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    @else
                        <x-reports.empty icon="queue-list" message="No stock movements recorded yet." class="h-40" />
                    @endif
                </flux:card>
            </div>
        @endisland
    </div>
</div>
