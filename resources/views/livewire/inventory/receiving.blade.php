<flux:card variant="default">

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="2">Receiving Inventory Stock</flux:heading>
            <flux:text class="mt-1">Manage incoming inventory item batches.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:input class="w-64" icon="magnifying-glass" size="sm" placeholder="Search receipts"
                wire:model.live.debounce.500ms="search" />
            <flux:select class="w-40" size="sm" wire:model.live="type" aria-label="Receipt type">
                <flux:select.option value="">All receipts</flux:select.option>
                <flux:select.option value="purchase">Purchases</flux:select.option>
                <flux:select.option value="donation">Donations</flux:select.option>
            </flux:select>
            <flux:modal.trigger name="create-receiving">
                <flux:button size="sm" icon="plus" x-on:click="$dispatch('receive-new')">Receive Stock</flux:button>
            </flux:modal.trigger>
            @livewire('inventory.receiving-create-form')
        </div>
    </div>

    <flux:table :paginate="$this->receipts">
        <flux:table.columns>
            <flux:table.column>Reference No.</flux:table.column>
            <flux:table.column>Supplier</flux:table.column>
            <flux:table.column>PO No.</flux:table.column>
            <flux:table.column>Received Date</flux:table.column>
            <flux:table.column>Received By</flux:table.column>
            <flux:table.column>Batches</flux:table.column>
            <flux:table.column>Total Qty</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column><span class="sr-only">Actions</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->receipts as $receipt)
            <flux:table.row :key="$receipt->id">
                <flux:table.cell variant="strong">
                    <div class="flex flex-wrap items-center gap-2">
                        {{ $receipt->reference_number }}
                        @if ($receipt->is_donation)
                            <flux:badge size="sm" color="sky">Donation</flux:badge>
                        @endif
                    </div>
                    @if ($receipt->parent_receipt_id)
                        <flux:text size="sm">↳ Follow-up of {{ $receipt->parent?->reference_number }}</flux:text>
                    @endif
                </flux:table.cell>
                <flux:table.cell>{{ $receipt->supplier?->name }}</flux:table.cell>
                <flux:table.cell variant="muted">{{ $receipt->purchase_order_number ?? '—' }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $receipt->received_date->format('M d, Y') }}
                    <flux:text size="sm">{{ $receipt->received_date->format('h:i A') }}</flux:text>
                </flux:table.cell>
                <flux:table.cell variant="muted">{{ $receipt->receiver?->name ?? '—' }}</flux:table.cell>
                <flux:table.cell>{{ $receipt->inventory_batches_count }}</flux:table.cell>
                <flux:table.cell>{{ number_format($receipt->inventory_batches_sum_received_qty ?? 0) }}
                </flux:table.cell>
                <flux:table.cell class="min-w-36">
                    <div class="flex flex-col items-start gap-1.5">
                        @if ($receipt->status === \App\Enums\ReceiptStatus::Closed && $receipt->closed_reason)
                            <flux:tooltip :content="$receipt->closed_reason">
                                <flux:badge size="sm" :color="$receipt->status->color()">
                                    {{ $receipt->status->label() }}
                                </flux:badge>
                            </flux:tooltip>
                        @else
                            <flux:badge size="sm" :color="$receipt->status->color()">
                                {{ $receipt->status->label() }}
                            </flux:badge>
                        @endif
                        @if ($progress = ($this->progressByReceipt[$receipt->id] ?? null))
                            <x-receiving.progress-mini :received="$progress['received']" :ordered="$progress['ordered']" />
                        @endif
                    </div>
                </flux:table.cell>
                <flux:table.cell align="end">
                    <flux:dropdown position="bottom" align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal"
                            aria-label="Actions for {{ $receipt->reference_number }}" />

                        <flux:menu>
                            <flux:menu.item icon="eye" wire:click="view({{ $receipt->id }})">
                                View details
                            </flux:menu.item>
                            <flux:menu.item icon="cube" wire:navigate
                                :href="route('inventory.stocks', ['receipt' => $receipt->id])">
                                View stock batches ({{ $receipt->inventory_batches_count }})
                            </flux:menu.item>
                            @php $isOpenRoot = $receipt->status === \App\Enums\ReceiptStatus::Partial && ! $receipt->parent_receipt_id; @endphp
                            @if ($receipt->isChainOpen() || $isOpenRoot)
                                <flux:menu.separator />
                            @endif
                            @if ($receipt->isChainOpen())
                                <flux:menu.item icon="truck" wire:click="receiveRemaining({{ $receipt->id }})">
                                    Receive remaining
                                </flux:menu.item>
                            @endif
                            @if ($isOpenRoot)
                                <flux:menu.item icon="x-circle" variant="danger" wire:click="confirmClose({{ $receipt->id }})">
                                    Close delivery…
                                </flux:menu.item>
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="9">
                    <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                        <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />
                        <flux:heading size="sm">No receipts found</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">
                            Received stock will appear here.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Close a partial delivery early --}}
    <flux:modal name="close-delivery" class="w-[480px] max-w-[95vw]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Close delivery?</flux:heading>
                <flux:text class="mt-1">
                    The remaining quantities will no longer be expected. No stock changes.
                </flux:text>
            </div>

            <flux:textarea wire:model="closeReason" label="Reason" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="closeDelivery">Close Delivery</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Receipt details (read-only) --}}
    <flux:modal name="view-receipt" class="w-[960px] max-w-[95vw]">
        @if ($viewed = $this->viewing)
            @php
                $groups = $this->viewingAttachments;
                $fileCount = collect($groups)->sum(fn ($g) => count($g['files']));
                $progress = $this->viewingLines;
                $timeline = $this->viewingTimeline;
                $chainClosed = ($timeline['status'] ?? $viewed->status) === \App\Enums\ReceiptStatus::Closed;
                $totalOrdered = $progress->sum('ordered');
                $totalReceived = $progress->sum('received');
                $rootRef = $groups[0]['reference_number'] ?? '';
                $batches = $viewed->inventoryBatches;
                $totalCost = $batches->sum(fn ($b) => $b->received_qty * (float) $b->unit_cost);
            @endphp

            <div class="space-y-6">
                {{-- Header --}}
                <div class="pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $viewed->reference_number }}</flux:heading>
                        @if ($viewed->status)
                            <flux:badge size="sm" :color="$viewed->status->color()">{{ $viewed->status->label() }}</flux:badge>
                        @endif
                        @if ($viewed->is_donation)
                            <flux:badge size="sm" color="sky">Donation</flux:badge>
                        @endif
                    </div>
                    <flux:text class="mt-1">{{ $viewed->supplier?->name ?? 'Unknown supplier' }}</flux:text>
                    @if ($viewed->parent_receipt_id)
                        <flux:text size="sm">Follow-up of {{ $viewed->parent?->reference_number }}</flux:text>
                    @endif
                </div>

                @if ($viewed->status === \App\Enums\ReceiptStatus::Closed && $viewed->closed_reason)
                    <flux:callout icon="information-circle">
                        <flux:callout.heading>Closed short</flux:callout.heading>
                        <flux:callout.text>{{ $viewed->closed_reason }}</flux:callout.text>
                    </flux:callout>
                @endif

                {{-- Receipt info --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                    @foreach ([
                        'PO No.' => $viewed->purchase_order_number,
                        'Delivery Date' => $viewed->delivery_date?->format('M d, Y h:i A'),
                        'Received Date' => $viewed->received_date?->format('M d, Y h:i A'),
                        'Received By' => $viewed->receiver?->name,
                        'Batches' => number_format($batches->count()),
                        'Total Qty' => number_format($batches->sum('received_qty')),
                    ] as $label => $value)
                        <div class="min-w-0">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:text variant="strong" class="break-words">{{ $value ?? '—' }}</flux:text>
                        </div>
                    @endforeach
                </div>

                @if ($viewed->remarks)
                    <div class="space-y-1">
                        <flux:text size="sm">Remarks</flux:text>
                        <flux:text variant="strong" class="whitespace-pre-line break-words">{{ $viewed->remarks }}</flux:text>
                    </div>
                @endif

                @if ($progress->isNotEmpty())
                    <flux:separator variant="subtle" />

                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <flux:heading>Delivery Progress</flux:heading>
                            <flux:text size="sm" class="tabular-nums">
                                {{ number_format($totalReceived) }} of {{ number_format($totalOrdered) }} received
                            </flux:text>
                        </div>

                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Item</flux:table.column>
                                <flux:table.column align="end">Ordered</flux:table.column>
                                <flux:table.column align="end">Received</flux:table.column>
                                <flux:table.column align="end">Outstanding</flux:table.column>
                                <flux:table.column>Status</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($progress as $line)
                                    <flux:table.row :key="'progress-'.$loop->index">
                                        <flux:table.cell>
                                            <flux:text variant="strong" class="font-medium">{{ $line['item']?->name ?? '—' }}</flux:text>
                                            <flux:text size="sm">{{ $line['item']?->item_code }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell align="end">{{ number_format($line['ordered']) }}</flux:table.cell>
                                        <flux:table.cell align="end">{{ number_format($line['received']) }}</flux:table.cell>
                                        <flux:table.cell align="end">{{ number_format($line['outstanding']) }}</flux:table.cell>
                                        <flux:table.cell>
                                            @if ($line['outstanding'] === 0)
                                                <flux:badge size="sm" color="green">Complete</flux:badge>
                                            @elseif ($chainClosed)
                                                <flux:badge size="sm" color="zinc">No longer expected</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="amber">{{ number_format($line['outstanding']) }} outstanding</flux:badge>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    @if ($timeline)
                        <div class="space-y-2">
                            <flux:heading>Deliveries in this chain</flux:heading>
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Reference</flux:table.column>
                                    <flux:table.column>Received Date</flux:table.column>
                                    <flux:table.column align="end">Items Received</flux:table.column>
                                    <flux:table.column align="end">Files</flux:table.column>
                                    <flux:table.column>Status</flux:table.column>
                                </flux:table.columns>
                                <flux:table.rows>
                                    @foreach ($timeline['deliveries'] as $delivery)
                                        @php $deliveryStatus = $delivery['status'] ?? null; @endphp
                                        <flux:table.row :key="'chain-'.$delivery['id']">
                                            <flux:table.cell>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <flux:text variant="strong" class="font-mono">{{ $delivery['reference_number'] }}</flux:text>
                                                    @if ($delivery['is_root'])
                                                        <flux:badge size="sm" color="zinc">Original</flux:badge>
                                                    @else
                                                        <flux:badge size="sm" color="sky">Follow-up</flux:badge>
                                                    @endif
                                                    @if ($delivery['is_current'])
                                                        <flux:badge size="sm" color="blue">This receipt</flux:badge>
                                                    @endif
                                                </div>
                                            </flux:table.cell>
                                            <flux:table.cell class="whitespace-nowrap">{{ $delivery['received_date']->format('M d, Y h:i A') }}</flux:table.cell>
                                            <flux:table.cell align="end">
                                                @if (count($delivery['items']))
                                                    {{ number_format(collect($delivery['items'])->sum('qty')) }} units
                                                    <flux:text size="sm">{{ count($delivery['items']) }} {{ \Illuminate\Support\Str::plural('item', count($delivery['items'])) }}</flux:text>
                                                @else
                                                    —
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell align="end">
                                                @if (count($delivery['files']))
                                                    {{ count($delivery['files']) }}
                                                @elseif ($delivery['inherits_files'])
                                                    <flux:text size="sm">uses {{ $rootRef }}</flux:text>
                                                @else
                                                    —
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                @if ($deliveryStatus)
                                                    <flux:badge size="sm" :color="$deliveryStatus->color()">{{ $deliveryStatus->label() }}</flux:badge>
                                                @else
                                                    —
                                                @endif
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </div>
                    @endif
                @endif

                <flux:separator variant="subtle" />

                {{-- Batches --}}
                <div class="space-y-2">
                    <flux:heading>Items Received</flux:heading>

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
                                    <flux:table.cell align="end">
                                        {{ number_format($line->received_qty) }}
                                        <flux:text inline size="sm">{{ $line->inventoryItem?->unit }}</flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">{{ number_format($line->remaining_qty) }}</flux:table.cell>
                                    <flux:table.cell align="end">{{ number_format((float) $line->unit_cost, 2) }}</flux:table.cell>
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

                    @if ($batches->isNotEmpty())
                        <flux:text size="sm" class="text-end">
                            Total cost <flux:text inline variant="strong">{{ number_format($totalCost, 2) }}</flux:text>
                        </flux:text>
                    @endif
                </div>

                <flux:separator variant="subtle" />

                {{-- Attachments --}}
                <div class="space-y-3">
                    <flux:heading>
                        Attachments
                        <flux:text inline size="sm">({{ $fileCount }})</flux:text>
                    </flux:heading>

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
                                                class="size-14 rounded-md object-cover" loading="lazy">
                                        </a>
                                    @else
                                        <div class="flex size-14 shrink-0 items-center justify-center rounded-md bg-zinc-100 dark:bg-white/10">
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

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" icon="cube" wire:navigate
                        :href="route('inventory.stocks', ['receipt' => $viewed->id])">
                        View stock batches
                    </flux:button>
                    <flux:modal.close>
                        <flux:button>Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>