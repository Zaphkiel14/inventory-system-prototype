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

    {{-- After a release: property units waiting for an ICS/PAR (the action arrives in Phase 3) --}}
    <div wire:ignore x-data="{ pendingUnits: 0, issuanceId: null, drafting: false }"
        x-on:stock-issued.window="pendingUnits = Number($event.detail?.pendingPropertyUnits ?? 0); issuanceId = $event.detail?.issuanceId ?? null">
        <div x-cloak x-show="pendingUnits > 0" class="mb-4">
            <flux:callout icon="document-text" color="sky">
                <flux:callout.heading>
                    <span x-text="pendingUnits === 1 ? '1 property unit issued' : pendingUnits + ' property units issued'"></span>
                </flux:callout.heading>
                <flux:callout.text>
                    Create the ICS or PAR drafts for them now. The units are grouped by class automatically.
                </flux:callout.text>

                <x-slot name="actions">
                    <flux:button size="sm" variant="primary" icon="document-plus"
                        x-bind:disabled="drafting || ! issuanceId"
                        x-on:click="drafting = true; $wire.draftPropertyDocuments(issuanceId).finally(() => drafting = false)">
                        Create ICS / PAR
                    </flux:button>
                </x-slot>

                <x-slot name="controls">
                    <flux:button icon="x-mark" variant="ghost" size="sm" x-on:click="pendingUnits = 0"
                        aria-label="Dismiss" />
                </x-slot>
            </flux:callout>
        </div>
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

    <flux:table :paginate="$this->requests" class="transition-opacity"
        wire:loading.delay.class="opacity-60" wire:target="search,status,gotoPage,nextPage,previousPage,setPage">
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

                    <flux:table.cell>{{ $request->requester_name }}</flux:table.cell>

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

                    <flux:table.cell align="end" class="whitespace-nowrap">
                        @if (filled($request->ris_number))
                            <flux:tooltip content="Print RIS {{ $request->ris_number }}">
                                <flux:button size="xs" variant="ghost" icon="printer" target="_blank"
                                    :href="route('requisitions.ris', $request)"
                                    aria-label="Print RIS {{ $request->ris_number }}" />
                            </flux:tooltip>
                        @endif

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
    <flux:modal name="release-request" class="w-[1100px] max-w-[95vw]" wire:close="dismissRelease">
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
                        Requested by {{ $releasing->requester_name }}
                        · Approved by {{ $releasing->latestApproval?->decidedBy?->name ?? '—' }}
                        on {{ $releasing->latestApproval?->decided_at?->format('M d, Y h:i A') ?? '—' }}
                    </flux:text>
                </div>

                @include('livewire.requisitions.partials.requester-details', ['requisition' => $releasing])

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
                            <flux:table.column class="w-48">Remarks</flux:table.column>
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

                                    <flux:table.cell>
                                        <flux:input size="sm" wire:model="form.issue_remarks.{{ $line->id }}"
                                            placeholder="Optional"
                                            aria-label="RIS remarks for {{ $item?->name }}" />
                                    </flux:table.cell>
                                @endif
                            </flux:table.row>

                            {{-- Property units: one row per physical unit in the planned release --}}
                            @if ($releasable)
                                @php
                                    $unitRows = $form->units[$line->id] ?? [];
                                    $lineError = $errors->first('form.units.'.$line->id);
                                @endphp

                                @if ($unitRows !== [] || $lineError)
                                    <flux:table.row :key="'release-units-'.$line->id">
                                        <flux:table.cell colspan="9" class="bg-zinc-50 dark:bg-white/5">
                                            <div class="space-y-3 py-1">
                                                @if ($lineError)
                                                    <flux:callout icon="exclamation-triangle" color="red">
                                                        <flux:callout.heading>{{ $item?->name }}: stock changed</flux:callout.heading>
                                                        <flux:callout.text>{{ $lineError }}</flux:callout.text>
                                                    </flux:callout>
                                                @endif

                                                @if ($unitRows !== [])
                                                    <flux:text size="sm">
                                                        Brand, model and serial for each unit of {{ $item?->name }}.
                                                        These go on the ICS or PAR.
                                                    </flux:text>

                                                    @foreach ($unitRows as $i => $unit)
                                                        @php $class = \App\Enums\PropertyClass::tryFrom((string) ($unit['property_class'] ?? '')); @endphp

                                                        <div wire:key="unit-{{ $line->id }}-{{ $i }}"
                                                            class="grid items-start gap-2 sm:grid-cols-3 lg:grid-cols-[6rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,14rem)]">
                                                            {{-- Below lg: unit, cost and class share one line above the three inputs --}}
                                                            <div class="flex flex-wrap items-center gap-x-2 pt-1 sm:col-span-3 lg:col-span-1 lg:block">
                                                                <flux:text size="sm" variant="strong">Unit {{ $i + 1 }}</flux:text>
                                                                <flux:text size="sm" class="tabular-nums">
                                                                    ₱{{ number_format((float) ($unit['unit_cost'] ?? 0), 2) }}
                                                                </flux:text>
                                                                @if ($class)
                                                                    <flux:badge size="sm" :color="$class->color()" class="lg:hidden">{{ $class->label() }}</flux:badge>
                                                                @endif
                                                            </div>

                                                            <flux:input size="sm" placeholder="Brand"
                                                                wire:model="form.units.{{ $line->id }}.{{ $i }}.brand"
                                                                aria-label="Brand, unit {{ $i + 1 }} of {{ $item?->name }}" />

                                                            <flux:input size="sm" placeholder="Model"
                                                                wire:model="form.units.{{ $line->id }}.{{ $i }}.model"
                                                                aria-label="Model, unit {{ $i + 1 }} of {{ $item?->name }}" />

                                                            <flux:input size="sm" placeholder="Serial no." class:input="font-mono"
                                                                wire:model="form.units.{{ $line->id }}.{{ $i }}.serial_number"
                                                                aria-label="Serial number, unit {{ $i + 1 }} of {{ $item?->name }}" />

                                                            <div class="hidden pt-1 lg:block">
                                                                @if ($class)
                                                                    <flux:badge size="sm" :color="$class->color()">{{ $class->label() }}</flux:badge>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endif
                            @endif
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                @if ($releasable)
                    <flux:error name="form.release" :deep="false" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-4">
                            <flux:input wire:model="form.received_by" label="Received By"
                                description="Person collecting the items." />
                            <flux:input wire:model="form.received_by_designation" label="Received By Designation"
                                placeholder="e.g. Nurse III" />
                        </div>
                        <div class="space-y-4">
                            <flux:input :value="auth()->user()?->name" label="Released By" readonly
                                description="Recorded as the signed-in user." />
                            <flux:input wire:model="form.released_by_designation" label="Released By Designation"
                                placeholder="e.g. Supply Officer II" />
                        </div>
                    </div>

                    <flux:textarea wire:model="form.remarks" label="Remarks" rows="2" placeholder="Optional" />

                    <flux:separator variant="subtle" />

                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button variant="primary" icon="truck" wire:click="confirmRelease">
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

    {{-- Release confirmation (opens on top of the release modal) --}}
    <flux:modal name="confirm-release" class="w-[520px] max-w-[95vw]">
        @if ($confirming = $this->releasing)
            @php
                $lines = $confirming->items->filter(fn ($line) => (int) ($form->release[$line->id] ?? 0) > 0);
            @endphp

            <div class="space-y-6">
                <div class="pr-8">
                    <flux:heading size="lg">Release items for {{ $confirming->request_number }}?</flux:heading>
                    <flux:text class="mt-1">
                        Stock will be deducted from the batches below. This can't be undone.
                    </flux:text>
                </div>

                @if ($lines->isNotEmpty())
                    <div class="divide-y divide-zinc-800/10 rounded-lg border border-zinc-800/10 dark:divide-white/10 dark:border-white/10">
                        @foreach ($lines as $line)
                            @php $plan = $this->preview[$line->id] ?? null; @endphp

                            <div class="flex items-start justify-between gap-4 px-4 py-3" wire:key="confirm-line-{{ $line->id }}">
                                <div class="min-w-0">
                                    <flux:text variant="strong" class="font-medium">{{ $line->inventoryItem?->name ?? '—' }}</flux:text>
                                    @foreach ($plan['allocations'] ?? [] as $allocation)
                                        <flux:text size="sm">
                                            {{ number_format($allocation['quantity']) }} from {{ $allocation['batch']->batch_number ?? '—' }}
                                            @if ($allocation['batch']->expiration_date)
                                                · exp {{ $allocation['batch']->expiration_date->format('M d, Y') }}
                                            @endif
                                        </flux:text>
                                    @endforeach
                                    @if (($plan['shortfall'] ?? 0) > 0)
                                        <flux:text size="sm" color="red">Short by {{ number_format($plan['shortfall']) }}</flux:text>
                                    @endif
                                </div>

                                <flux:text variant="strong" class="shrink-0 font-medium tabular-nums">
                                    {{ number_format((int) $form->release[$line->id]) }}
                                    <flux:text inline size="sm">{{ $line->inventoryItem?->unit }}</flux:text>
                                </flux:text>
                            </div>
                        @endforeach
                    </div>
                @else
                    <flux:callout icon="exclamation-triangle" color="amber">
                        <flux:callout.text>No quantities entered. Enter a quantity for at least one item.</flux:callout.text>
                    </flux:callout>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text size="sm">Received By</flux:text>
                        <flux:text variant="strong">{{ $form->received_by }}</flux:text>
                        @if (filled($form->received_by_designation))
                            <flux:text size="sm">{{ $form->received_by_designation }}</flux:text>
                        @endif
                    </div>
                    <div>
                        <flux:text size="sm">Released By</flux:text>
                        <flux:text variant="strong">{{ auth()->user()?->name }}</flux:text>
                        @if (filled($form->released_by_designation))
                            <flux:text size="sm">{{ $form->released_by_designation }}</flux:text>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Go Back</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" icon="truck" wire:click="release"
                        x-on:click="$flux.modal('confirm-release').close()"
                        :disabled="$lines->isEmpty()">
                        Confirm Release
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>
