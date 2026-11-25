{{-- Receipt details (read-only). Expects $viewed = $this->viewing. --}}
@php
    $summary = $this->viewingSummary;
    $groups = $this->viewingAttachments;
    $fileCount = collect($groups)->flatMap(fn ($group) => $group['files'])->count();
    $rootRef = $groups[0]['reference_number'] ?? '';
    $batches = $viewed->inventoryBatches;
    $isChain = (bool) ($summary['is_chain'] ?? false);
@endphp

<div class="space-y-5" wire:key="receipt-details-{{ $viewed->id }}">
    {{-- Header --}}
    <div class="pr-8">
        <div class="flex flex-wrap items-center gap-2">
            <flux:heading size="lg" class="font-mono">{{ $viewed->reference_number }}</flux:heading>
            @if ($viewed->status)
                <flux:badge size="sm" :color="$viewed->status->color()">{{ $viewed->status->label() }}</flux:badge>
            @endif
            @if ($viewed->is_donation)
                <flux:badge size="sm" color="sky">Donation</flux:badge>
            @endif
        </div>
        <flux:text size="sm" class="mt-1">
            {{ $viewed->supplier?->name ?? 'Unknown supplier' }}
            &middot; {{ $viewed->received_date?->format('M d, Y h:i A') ?? '—' }}
            &middot; {{ $viewed->receiver?->name ?? '—' }}
        </flux:text>
        @if ($viewed->parent_receipt_id)
            <flux:text size="sm">↳ Follow-up of {{ $viewed->parent?->reference_number }}</flux:text>
        @endif
    </div>

    @if ($summary['is_closed'] ?? false)
        <flux:callout icon="information-circle" color="zinc">
            <flux:callout.heading>Closed short</flux:callout.heading>
            @if ($closedReason = ($this->viewingTimeline['closed_reason'] ?? null) ?: $viewed->closed_reason)
                <flux:callout.text>{{ $closedReason }}</flux:callout.text>
            @endif
        </flux:callout>
    @endif

    {{-- Summary strip --}}
    @if ($summary)
        <dl @class([
            'grid grid-cols-2 gap-3',
            'sm:grid-cols-4' => $isChain,
            'sm:grid-cols-3' => ! $isChain,
        ])>
            <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <dt><flux:text size="sm">Units received</flux:text></dt>
                <dd><flux:text variant="strong" class="text-lg font-semibold tabular-nums">{{ number_format($summary['units']) }}</flux:text></dd>
            </div>
            <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <dt><flux:text size="sm">Batches</flux:text></dt>
                <dd><flux:text variant="strong" class="text-lg font-semibold tabular-nums">{{ number_format($summary['batches']) }}</flux:text></dd>
            </div>
            <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                <dt><flux:text size="sm">Total cost</flux:text></dt>
                <dd><flux:text variant="strong" class="text-lg font-semibold tabular-nums">{{ number_format((float) $summary['total_cost'], 2) }}</flux:text></dd>
            </div>
            @if ($isChain)
                <div @class([
                    'rounded-lg border px-3 py-2',
                    'border-amber-300 bg-amber-50/60 dark:border-amber-500/40 dark:bg-amber-500/10' => ($summary['outstanding'] ?? 0) > 0 && ! $summary['is_closed'],
                    'border-zinc-200 dark:border-zinc-700' => ($summary['outstanding'] ?? 0) === 0 || $summary['is_closed'],
                ])>
                    <dt><flux:text size="sm">Outstanding</flux:text></dt>
                    <dd>
                        <flux:text variant="strong" class="text-lg font-semibold tabular-nums">
                            {{ number_format($summary['outstanding'] ?? 0) }}
                            <span class="text-sm font-normal text-zinc-500 dark:text-zinc-400">
                                &middot; {{ number_format($summary['received'] ?? 0) }} of {{ number_format($summary['ordered'] ?? 0) }}
                            </span>
                        </flux:text>
                    </dd>
                </div>
            @endif
        </dl>
    @endif

    {{-- Compact details --}}
    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
        <div class="flex gap-2">
            <dt><flux:text size="sm" inline>PO No.</flux:text></dt>
            <dd><flux:text size="sm" variant="strong" inline>{{ $viewed->purchase_order_number ?? '—' }}</flux:text></dd>
        </div>
        <div class="flex gap-2">
            <dt><flux:text size="sm" inline>Delivery date</flux:text></dt>
            <dd><flux:text size="sm" variant="strong" inline>{{ $viewed->delivery_date?->format('M d, Y h:i A') ?? '—' }}</flux:text></dd>
        </div>
        @if ($viewed->remarks)
            <div class="flex gap-2 sm:col-span-2">
                <dt class="shrink-0"><flux:text size="sm" inline>Remarks</flux:text></dt>
                <dd class="min-w-0"><flux:text size="sm" variant="strong" class="whitespace-pre-line break-words">{{ $viewed->remarks }}</flux:text></dd>
            </div>
        @endif
    </dl>

    {{-- Tabs --}}
    <div x-data="{ tab: 'items' }" class="space-y-4">
        <div role="tablist" aria-label="Receipt sections"
            class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
            @foreach (array_filter([
                'items' => 'Items',
                'progress' => $isChain ? 'Delivery progress' : null,
                'attachments' => 'Attachments ('.$fileCount.')',
            ]) as $key => $label)
                <button type="button" role="tab" id="receipt-tab-{{ $key }}"
                    aria-controls="receipt-panel-{{ $key }}"
                    x-on:click="tab = @js($key)"
                    x-bind:aria-selected="tab === @js($key)"
                    x-bind:class="tab === @js($key)
                        ? 'border-zinc-800 text-zinc-800 dark:border-white dark:text-white'
                        : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
                    class="-mb-px whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Items --}}
        <div role="tabpanel" id="receipt-panel-items" aria-labelledby="receipt-tab-items" x-show="tab === 'items'">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Item</flux:table.column>
                    <flux:table.column>Batch / Lot</flux:table.column>
                    <flux:table.column>Location</flux:table.column>
                    <flux:table.column>Expiration</flux:table.column>
                    <flux:table.column align="end">Received</flux:table.column>
                    <flux:table.column align="end">Remaining</flux:table.column>
                    <flux:table.column align="end">Unit Cost</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($batches as $line)
                        <flux:table.row :key="'view-batch-'.$line->id">
                            <flux:table.cell>
                                <flux:text variant="strong" class="font-medium">{{ $line->inventoryItem?->name ?? '—' }}</flux:text>
                                <flux:text size="sm">{{ $line->inventoryItem?->item_code }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $line->batch_number ?? '—' }}
                                <flux:text size="sm">{{ $line->lot_number ?? '—' }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell>{{ $line->storageLocation?->name ?? '—' }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $line->expiration_date?->format('M d, Y') ?? '—' }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">
                                {{ number_format($line->received_qty) }}
                                <flux:text inline size="sm">{{ $line->inventoryItem?->unit }}</flux:text>
                            </flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ number_format($line->remaining_qty) }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ number_format((float) $line->unit_cost, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7">
                                <flux:text size="sm">No batches were recorded on this receipt.</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            @if ($batches->isNotEmpty() && $summary)
                <flux:text size="sm" class="mt-2 text-end">
                    Total cost <flux:text inline variant="strong" class="tabular-nums">{{ number_format((float) $summary['total_cost'], 2) }}</flux:text>
                </flux:text>
            @endif
        </div>

        {{-- Delivery progress --}}
        @if ($isChain && $this->viewingTimeline)
            <div role="tabpanel" id="receipt-panel-progress" aria-labelledby="receipt-tab-progress"
                x-show="tab === 'progress'" x-cloak>
                <x-receiving.delivery-timeline :timeline="$this->viewingTimeline" />
            </div>
        @endif

        {{-- Attachments --}}
        <div role="tabpanel" id="receipt-panel-attachments" aria-labelledby="receipt-tab-attachments"
            x-show="tab === 'attachments'" x-cloak class="space-y-4">
            @if ($fileCount > 0)
                @foreach ($groups as $group)
                    @continue(empty($group['files']) && $loop->first)
                    <div class="space-y-2" wire:key="view-group-{{ $group['receipt_id'] }}">
                        <flux:text size="sm" variant="strong">
                            {{ $group['reference_number'] }}@if ($group['is_current']) (this receipt)@endif
                        </flux:text>
                        @if (empty($group['files']))
                            <flux:badge size="sm" color="zinc">Uses files from {{ $rootRef }}</flux:badge>
                        @endif
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($group['files'] as $file)
                                <flux:card size="sm" class="flex items-center gap-3" wire:key="view-file-{{ $group['receipt_id'] }}-{{ $file['id'] }}">
                                    @if ($file['image'] && $file['url'])
                                        <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="shrink-0">
                                            <img src="{{ $file['url'] }}" alt="Attachment {{ $file['name'] }}"
                                                class="size-12 rounded-md object-cover" loading="lazy">
                                        </a>
                                    @else
                                        <div class="flex size-12 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-white/10">
                                            <flux:icon :name="$file['pdf'] ? 'document-text' : 'paper-clip'" class="text-zinc-400" />
                                        </div>
                                    @endif

                                    <div class="min-w-0 flex-1">
                                        <flux:text variant="strong" class="truncate">{{ $file['name'] }}</flux:text>
                                        @if ($file['url'])
                                            <flux:text size="sm">{{ \Illuminate\Support\Number::fileSize($file['size'] ?? 0) }}</flux:text>
                                        @else
                                            <flux:text size="sm" color="red">File is missing from storage</flux:text>
                                        @endif
                                    </div>

                                    @if ($file['url'])
                                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square"
                                            :href="$file['url']" target="_blank" rel="noopener"
                                            aria-label="Open {{ $file['name'] }}" />
                                    @endif
                                </flux:card>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <flux:text size="sm">No files were attached to this receipt.</flux:text>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-4 sm:flex-row sm:justify-end dark:border-zinc-700">
        <flux:modal.close>
            <flux:button class="w-full sm:w-auto">Close</flux:button>
        </flux:modal.close>
        <flux:button variant="ghost" icon="cube" wire:navigate
            :href="route('inventory.stocks', ['receipt' => $viewed->id])">
            View stock batches
        </flux:button>
        @if ($summary['can_receive_remaining'] ?? false)
            <flux:button variant="primary" icon="truck" wire:click="receiveRemaining({{ $viewed->id }})">
                Receive remaining
            </flux:button>
        @endif
    </div>
</div>
