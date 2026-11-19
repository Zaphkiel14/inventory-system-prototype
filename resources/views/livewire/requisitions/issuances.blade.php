<flux:card variant="default">

    <div class="mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <flux:heading size="xl" level="2">Requisition Issuance</flux:heading>
            @if ($this->releasableCount > 0)
                <flux:badge size="sm" color="sky">{{ number_format($this->releasableCount) }} to release</flux:badge>
            @endif
        </div>
        <flux:text class="mt-1">
            Release approved quantities. Stock is drawn from batches automatically — FEFO for items with an expiry date, FIFO for everything else.
        </flux:text>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm"
                placeholder="Search request no., issuance no., purpose, item, requester, receiver"
                wire:model.live.debounce.500ms="search" />
        </div>

        <flux:select size="sm" class="md:w-48" wire:model.live="status">
            <flux:select.option value="releasable">Ready to release</flux:select.option>
            @foreach ($this->stages() as $option)
                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
            @endforeach
            <flux:select.option value="">All</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->requests">
        <flux:table.columns>
            <flux:table.column>Request No.</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column>Approved</flux:table.column>
            <flux:table.column>Purpose</flux:table.column>
            <flux:table.column align="end">Items</flux:table.column>
            <flux:table.column align="end">Approved Qty</flux:table.column>
            <flux:table.column align="end">Released</flux:table.column>
            <flux:table.column align="end">Outstanding</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->requests as $request)
                @php $releasable = in_array($request->status, \App\Livewire\Forms\IssuancesForm::RELEASABLE, true); @endphp

                <flux:table.row :key="$request->id">
                    <flux:table.cell variant="strong" class="whitespace-nowrap font-medium">
                        {{ $request->request_number }}
                    </flux:table.cell>

                    <flux:table.cell>{{ $request->requester?->name ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $request->latestApproval?->decided_at?->format('M d, Y') ?? '—' }}
                        <flux:text size="sm">{{ $request->latestApproval?->decided_at?->diffForHumans() }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell class="max-w-56">
                        <flux:text class="truncate" :title="$request->purpose">{{ $request->purpose }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end">{{ number_format($request->items_count) }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format((int) $request->items_sum_approved_qty) }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format((int) $request->items_sum_released_qty) }}</flux:table.cell>
                    <flux:table.cell align="end" variant="strong">{{ number_format((int) $request->items_sum_outstanding_qty) }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($request->status)
                            <flux:badge size="sm" :color="$request->status->color()">{{ $request->status->label() }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        @if ($releasable)
                            <flux:button size="xs" variant="primary" icon="truck" wire:click="open({{ $request->id }})">
                                Release
                            </flux:button>
                        @else
                            <flux:button size="xs" variant="ghost" icon="eye" wire:click="open({{ $request->id }})">
                                View
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="truck" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">
                                {{ $status === 'releasable' && $search === '' ? 'Nothing waiting to be released' : 'No requests found' }}
                            </flux:heading>
                            <flux:text size="sm">
                                {{ $status === 'releasable' && $search === '' ? 'Approved requests will appear here.' : 'Try a different search or filter.' }}
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Release / history modal --}}
    <flux:modal name="release-request" class="w-[1100px] max-w-[95vw]">
        @if ($releasing = $this->releasing)
            @php $releasable = in_array($releasing->status, \App\Livewire\Forms\IssuancesForm::RELEASABLE, true); @endphp

            <div class="space-y-6">
                <div class="pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $releasing->request_number }}</flux:heading>
                        @if ($releasing->status)
                            <flux:badge size="sm" :color="$releasing->status->color()">{{ $releasing->status->label() }}</flux:badge>
                        @endif
                    </div>
                    <flux:text class="mt-1">
                        Requested by {{ $releasing->requester?->name ?? '—' }}
                        · Approved by {{ $releasing->latestApproval?->decidedBy?->name ?? '—' }}
                        on {{ $releasing->latestApproval?->decided_at?->format('M d, Y h:i A') ?? '—' }}
                    </flux:text>
                </div>

                <div>
                    <flux:text size="sm">Purpose</flux:text>
                    <flux:text variant="strong">{{ $releasing->purpose }}</flux:text>
                </div>

                @error('form.requisition_id')
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror

                {{-- Lines --}}
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="end">Requested</flux:table.column>
                        <flux:table.column align="end">Approved</flux:table.column>
                        <flux:table.column align="end">Released</flux:table.column>
                        <flux:table.column align="end">Outstanding</flux:table.column>
                        @if ($releasable)
                            <flux:table.column align="end">On Hand</flux:table.column>
                            <flux:table.column align="end" class="w-36">Release Now</flux:table.column>
                            <flux:table.column>Drawn From</flux:table.column>
                        @endif
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($releasing->items as $line)
                            @php
                                $item = $line->inventoryItem;
                                $plan = $this->preview[$line->id] ?? null;
                            @endphp

                            <flux:table.row :key="'release-line-'.$line->id">
                                <flux:table.cell>
                                    <div class="flex items-center gap-2">
                                        <flux:text variant="strong" class="font-medium">{{ $item?->name ?? '—' }}</flux:text>
                                        @if ($item)
                                            <flux:badge size="sm" color="zinc">{{ \App\Services\Inventory\BatchAllocator::methodFor($item) }}</flux:badge>
                                        @endif
                                    </div>
                                    <flux:text size="sm">{{ $item?->item_code }} · {{ $item?->unit }}</flux:text>
                                </flux:table.cell>

                                <flux:table.cell align="end">{{ number_format($line->requested_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format((int) $line->approved_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format($line->released_qty) }}</flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ number_format($line->outstanding_qty) }}</flux:table.cell>

                                @if ($releasable)
                                    @php $onHand = $this->onHand[$line->inventory_item_id] ?? 0; @endphp

                                    <flux:table.cell align="end">
                                        <flux:text inline :color="$onHand < $line->outstanding_qty ? 'amber' : null">
                                            {{ number_format($onHand) }}
                                        </flux:text>
                                    </flux:table.cell>

                                    <flux:table.cell align="end">
                                        @if ($line->outstanding_qty > 0)
                                            <flux:input type="number" size="sm" min="0"
                                                max="{{ min($line->outstanding_qty, $onHand) }}"
                                                wire:model.live.debounce.400ms="form.release.{{ $line->id }}"
                                                aria-label="Quantity to release for {{ $item?->name }}" />
                                        @else
                                            <flux:text inline size="sm">Complete</flux:text>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell class="min-w-56">
                                        @if ($plan)
                                            <div class="space-y-1">
                                                @foreach ($plan['allocations'] as $allocation)
                                                    <flux:text size="sm">
                                                        <flux:text inline size="sm" variant="strong">{{ number_format($allocation['quantity']) }}</flux:text>
                                                        from {{ $allocation['batch']->batch_number ?? '—' }}
                                                        @if ($allocation['batch']->expiration_date)
                                                            · exp {{ $allocation['batch']->expiration_date->format('M d, Y') }}
                                                        @endif
                                                    </flux:text>
                                                @endforeach

                                                @if ($plan['shortfall'] > 0)
                                                    <flux:text size="sm" color="red">
                                                        Short by {{ number_format($plan['shortfall']) }}
                                                    </flux:text>
                                                @endif
                                            </div>
                                        @else
                                            <flux:text inline size="sm">—</flux:text>
                                        @endif
                                    </flux:table.cell>
                                @endif
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                @if ($releasable)
                    <flux:error name="form.release" :deep="false" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:input wire:model="form.received_by" label="Received By"
                            description="Person collecting the items." />
                        <flux:input :value="auth()->user()?->name" label="Released By" readonly
                            description="Recorded as the signed-in user." />
                    </div>

                    <flux:textarea wire:model="form.remarks" label="Remarks" rows="2" placeholder="Optional" />

                    <flux:separator variant="subtle" />

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button variant="primary" icon="truck" wire:click="release"
                            wire:confirm="Release these items? Stock will be deducted from the batches shown.">
                            Release Items
                        </flux:button>
                    </div>
                @endif

                {{-- Previous issuances --}}
                @if ($releasing->stockIssuances->isNotEmpty())
                    <flux:separator variant="subtle" />

                    <div class="space-y-3">
                        <flux:heading>Issuance History</flux:heading>

                        @foreach ($releasing->stockIssuances as $issuance)
                            <flux:card size="sm" wire:key="issuance-{{ $issuance->id }}" class="space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <flux:text variant="strong" class="font-medium">{{ $issuance->issuance_number }}</flux:text>
                                    <flux:text size="sm">
                                        {{ $issuance->released_at?->format('M d, Y h:i A') }}
                                        · Released by {{ $issuance->released_by ?: '—' }}
                                        · Received by {{ $issuance->received_by ?: '—' }}
                                    </flux:text>
                                </div>

                                <flux:table>
                                    <flux:table.columns>
                                        <flux:table.column>Item</flux:table.column>
                                        <flux:table.column align="end">Released</flux:table.column>
                                        <flux:table.column>Batches</flux:table.column>
                                    </flux:table.columns>
                                    <flux:table.rows>
                                        @foreach ($issuance->stockIssuanceItems as $issued)
                                            <flux:table.row :key="'issued-'.$issued->id">
                                                <flux:table.cell>{{ $issued->requisitionItem?->inventoryItem?->name ?? '—' }}</flux:table.cell>
                                                <flux:table.cell align="end" variant="strong">{{ number_format($issued->released_qty) }}</flux:table.cell>
                                                <flux:table.cell>
                                                    @foreach ($issued->batches as $drawn)
                                                        <flux:text size="sm">
                                                            {{ number_format($drawn->quantity) }} from
                                                            {{ $drawn->inventoryBatch?->batch_number ?? '—' }}
                                                            · Lot {{ $drawn->inventoryBatch?->lot_number ?? '—' }}
                                                            @if ($drawn->inventoryBatch?->expiration_date)
                                                                · exp {{ $drawn->inventoryBatch->expiration_date->format('M d, Y') }}
                                                            @endif
                                                        </flux:text>
                                                    @endforeach
                                                </flux:table.cell>
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>

                                @if ($issuance->remarks)
                                    <flux:text size="sm">{{ $issuance->remarks }}</flux:text>
                                @endif
                            </flux:card>
                        @endforeach
                    </div>
                @endif

                @unless ($releasable)
                    <div class="flex justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                    </div>
                @endunless
            </div>
        @endif
    </flux:modal>
</flux:card>
