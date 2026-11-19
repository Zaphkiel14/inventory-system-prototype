@php
    $summary = $this->summary;
    $timeline = $this->timeline;
    $hasTimeline = collect($timeline)->contains(fn ($r) => $r['expired'] > 0 || $r['expiring'] > 0);
    $filtered = $search !== '' || $status !== '' || $location !== '' || $sort !== 'soonest';

    $tiles = [
        'expired' => ['label' => 'Expired', 'icon' => 'exclamation-triangle', 'tone' => 'red'],
        '7' => ['label' => 'Within 7 days', 'icon' => 'fire', 'tone' => 'orange'],
        '30' => ['label' => 'Within 30 days', 'icon' => 'clock', 'tone' => 'amber'],
        '90' => ['label' => 'Within 90 days', 'icon' => 'calendar-days', 'tone' => 'sky'],
    ];

    $toneClasses = [
        'red' => ['ring-red-500/60 dark:ring-red-400/60', 'text-red-600 dark:text-red-400'],
        'orange' => ['ring-orange-500/60 dark:ring-orange-400/60', 'text-orange-600 dark:text-orange-400'],
        'amber' => ['ring-amber-500/60 dark:ring-amber-400/60', 'text-amber-600 dark:text-amber-400'],
        'sky' => ['ring-sky-500/60 dark:ring-sky-400/60', 'text-sky-600 dark:text-sky-400'],
    ];
@endphp

<div class="flex flex-col gap-6">

    {{-- Header --}}
    <flux:card variant="default">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <flux:heading size="xl" level="2">Expiration Monitoring</flux:heading>
                <flux:text class="mt-1">Track batches nearing their expiration date and write off expired stock.</flux:text>
            </div>

            @if (count($selected))
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="$set('selected', [])">Clear selection</flux:button>
                    <flux:button size="sm" variant="danger" icon="trash" wire:click="confirmWriteOff">
                        Write off {{ count($selected) }} selected
                    </flux:button>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Summary tiles (click to filter) --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tiles as $key => $tile)
            @php
                $data = $summary[$key];
                [$ring, $text] = $toneClasses[$tile['tone']];
                $active = $status === (string) $key;
            @endphp
            <button type="button" wire:click="filterStatus('{{ $key }}')" aria-pressed="{{ $active ? 'true' : 'false' }}"
                @class([
                    'rounded-xl text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent',
                    "ring-2 $ring" => $active,
                ])>
                <flux:card size="sm" class="flex h-full flex-col gap-2 hover:bg-zinc-50 dark:hover:bg-white/5">
                    <div class="flex items-center justify-between gap-2">
                        <flux:text size="sm">{{ $tile['label'] }}</flux:text>
                        <flux:icon :name="$tile['icon']" variant="micro" @class([$text => $data['batches'] > 0, 'text-zinc-400' => $data['batches'] === 0]) />
                    </div>

                    <div class="flex items-baseline gap-2">
                        <flux:heading size="xl" @class(['tabular-nums', "$text!" => $data['batches'] > 0])>
                            {{ number_format($data['batches']) }}
                        </flux:heading>
                        <flux:text size="sm">{{ Str::plural('batch', $data['batches']) }}</flux:text>
                    </div>

                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ number_format($data['units']) }} units · {{ number_format($data['value'], 2) }} value
                    </flux:text>
                </flux:card>
            </button>
        @endforeach
    </div>

    {{-- Value at risk by month --}}
    <flux:card>
        <flux:heading>Value at risk</flux:heading>
        <flux:text size="sm" class="mb-4">Value of stock already expired, and expiring in each of the next six months</flux:text>

        @if ($hasTimeline)
            <x-chart :value="$timeline" stacked class="h-56">
                <x-chart.bar field="expired" label="Expired" class="text-red-500 dark:text-red-400" />
                <x-chart.bar field="expiring" label="Expiring" class="text-amber-500 dark:text-amber-400" />
                <x-chart.axis axis="x" field="month" />
                <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                <x-chart.cursor />
                <x-chart.tooltip :value-format="['minimumFractionDigits' => 2, 'maximumFractionDigits' => 2]" />
            </x-chart>
        @else
            <x-reports.empty icon="check-circle" message="Nothing expired or expiring in the next six months." class="h-40" />
        @endif
    </flux:card>

    {{-- Batch list --}}
    <flux:card>
        <div class="mb-4">
            <flux:heading>Tracked batches</flux:heading>
            <flux:text size="sm">Batches with an expiration date and stock left. Tick expired batches to write several off at once.</flux:text>
        </div>

        {{-- Toolbar --}}
        <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-center">
            <div class="lg:flex-1">
                <flux:input icon="magnifying-glass" size="sm" placeholder="Search item, code, batch or lot"
                    wire:model.live.debounce.500ms="search" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:select size="sm" class="w-full sm:w-52" wire:model.live="status" aria-label="Status">
                    @foreach (\App\Livewire\Monitoring\ExpirationMonitoring::STATUSES as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" class="w-full sm:w-52" wire:model.live="location" aria-label="Location">
                    <flux:select.option value="">All locations</flux:select.option>
                    @foreach ($this->locations as $loc)
                        <flux:select.option value="{{ $loc->id }}">{{ $loc->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select size="sm" class="w-full sm:w-44" wire:model.live="sort" aria-label="Sort">
                    @foreach (\App\Livewire\Monitoring\ExpirationMonitoring::SORTS as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($filtered)
                    <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="clearFilters" tooltip="Clear filters" />
                @endif
            </div>
        </div>

        <div wire:loading.class="opacity-60" wire:target="search,status,location,sort,filterStatus,clearFilters,gotoPage,nextPage,previousPage">
            @if ($this->batches->isNotEmpty())
                <flux:table :paginate="$this->batches">
                    <flux:table.columns>
                        <flux:table.column class="w-8"><span class="sr-only">Select</span></flux:table.column>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column>Batch / Lot</flux:table.column>
                        <flux:table.column>Location</flux:table.column>
                        <flux:table.column align="end">Remaining</flux:table.column>
                        <flux:table.column align="end">Value</flux:table.column>
                        <flux:table.column>Expires</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column align="end"><span class="sr-only">Actions</span></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->batches as $batch)
                            @php
                                $days = (int) today()->diffInDays($batch->expiration_date, false);
                                $expired = $days < 0;

                                [$badge, $color] = match (true) {
                                    $expired => [abs($days).' '.Str::plural('day', abs($days)).' ago', 'red'],
                                    $days === 0 => ['Expires today', 'red'],
                                    $days <= 7 => [$days.' '.Str::plural('day', $days).' left', 'orange'],
                                    $days <= 30 => [$days.' days left', 'amber'],
                                    $days <= 90 => [$days.' days left', 'sky'],
                                    default => [$days.' days left', 'zinc'],
                                };
                            @endphp

                            <flux:table.row wire:key="exp-{{ $batch->id }}">
                                <flux:table.cell>
                                    @if ($expired)
                                        <flux:checkbox wire:model.live="selected" value="{{ $batch->id }}"
                                            aria-label="Select batch {{ $batch->batch_number }}" />
                                    @endif
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div class="font-medium text-zinc-800 dark:text-white">{{ $batch->inventoryItem?->name ?? 'Unknown item' }}</div>
                                    <flux:text size="sm">{{ $batch->inventoryItem?->item_code }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>
                                    <div>{{ $batch->batch_number ?? '—' }}</div>
                                    <flux:text size="sm">{{ $batch->lot_number ?? '—' }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell>{{ $batch->storageLocation?->name ?? '—' }}</flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    {{ number_format($batch->remaining_qty) }}
                                    <span class="text-zinc-400">{{ $batch->inventoryItem?->unit }}</span>
                                </flux:table.cell>

                                <flux:table.cell align="end" class="tabular-nums">
                                    {{ number_format($batch->remaining_qty * (float) $batch->unit_cost, 2) }}
                                </flux:table.cell>

                                <flux:table.cell class="whitespace-nowrap">{{ $batch->expiration_date->format('M d, Y') }}</flux:table.cell>

                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$color">{{ $badge }}</flux:badge>
                                </flux:table.cell>

                                <flux:table.cell align="end">
                                    @if ($expired)
                                        <flux:button size="xs" variant="ghost" icon="trash" tooltip="Write off"
                                            aria-label="Write off batch {{ $batch->batch_number }}"
                                            wire:click="confirmWriteOff({{ $batch->id }})" />
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <div class="flex flex-col items-center justify-center gap-2 py-12 text-center">
                    <flux:icon name="shield-check" variant="outline" class="size-8 text-zinc-400" />
                    <flux:heading size="sm">No batches found</flux:heading>
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        {{ $filtered ? 'Try a different search or filter.' : 'Batches with an expiration date and stock left will appear here.' }}
                    </flux:text>
                </div>
            @endif
        </div>
    </flux:card>

    {{-- Write-off confirmation --}}
    <flux:modal name="expiry-write-off" class="w-[640px] max-w-[95vw]">
        <form wire:submit="writeOff" class="space-y-6">
            <div>
                <flux:heading size="lg">Write off expired stock</flux:heading>
                <flux:text class="mt-1">
                    Each batch is set to zero with a permanent Expiry Write-off adjustment. This can't be undone.
                </flux:text>
            </div>

            @php $writeOff = $this->writeOffBatches; @endphp

            <div class="max-h-56 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 dark:divide-white/10 dark:border-white/10">
                @foreach ($writeOff as $batch)
                    <div class="flex items-center justify-between gap-4 px-3 py-2" wire:key="wo-{{ $batch->id }}">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-zinc-800 dark:text-white">{{ $batch->inventoryItem?->name }}</div>
                            <flux:text size="sm">
                                {{ $batch->batch_number ?? '—' }} · expired {{ $batch->expiration_date->format('M d, Y') }}
                            </flux:text>
                        </div>
                        <div class="shrink-0 text-right text-sm tabular-nums">
                            <div class="font-medium text-red-600 dark:text-red-400">−{{ number_format($batch->remaining_qty) }} {{ $batch->inventoryItem?->unit }}</div>
                            <flux:text size="sm">{{ number_format($batch->remaining_qty * (float) $batch->unit_cost, 2) }}</flux:text>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between text-sm">
                <flux:text>{{ $writeOff->count() }} {{ Str::plural('batch', $writeOff->count()) }}</flux:text>
                <flux:text variant="strong" class="tabular-nums">
                    {{ number_format($writeOff->sum('remaining_qty')) }} units ·
                    {{ number_format($writeOff->sum(fn ($b) => $b->remaining_qty * (float) $b->unit_cost), 2) }} value
                </flux:text>
            </div>

            <flux:error name="writeOffIds" />

            <flux:textarea wire:model="reason" label="Reason" rows="2" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="requestedBy" label="Requested By" />
                <flux:input wire:model="approvedBy" label="Approved By" placeholder="Name of approver" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="danger" wire:loading.attr="disabled" wire:target="writeOff">
                    <span wire:loading.remove wire:target="writeOff">Write off</span>
                    <span wire:loading wire:target="writeOff">Writing off...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
