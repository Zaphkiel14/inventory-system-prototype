<flux:card variant="default">

    <div class="mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <flux:heading size="xl" level="2">Requisition Approvals</flux:heading>
            @if ($this->pendingCount > 0)
                <flux:badge size="sm" color="amber">{{ number_format($this->pendingCount) }} pending</flux:badge>
            @endif
        </div>
        <flux:text class="mt-1">
            Review requests and decide how much of each item may be dispensed. Approved quantities move to issuance.
        </flux:text>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm" placeholder="Search request no., purpose, item, requester"
                wire:model.live.debounce.500ms="search" />
        </div>

        <flux:select size="sm" class="md:w-48" wire:model.live="status">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach (\App\Enums\RequisitionStatus::cases() as $option)
                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->requests">
        <flux:table.columns>
            <flux:table.column>Request No.</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column>Purpose</flux:table.column>
            <flux:table.column align="end">Items</flux:table.column>
            <flux:table.column align="end">Requested</flux:table.column>
            <flux:table.column align="end">Approved</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Decided By</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->requests as $request)
                @php $decision = $request->latestApproval; @endphp

                <flux:table.row :key="$request->id">
                    <flux:table.cell variant="strong" class="whitespace-nowrap font-medium">
                        {{ $request->request_number }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $request->submitted_at?->format('M d, Y') }}
                        <flux:text size="sm">{{ $request->submitted_at?->diffForHumans() }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>{{ $request->requester?->name ?? '—' }}</flux:table.cell>

                    <flux:table.cell class="max-w-64">
                        <flux:text class="truncate" :title="$request->purpose">{{ $request->purpose }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end">{{ number_format($request->items_count) }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format((int) $request->items_sum_requested_qty) }}</flux:table.cell>
                    <flux:table.cell align="end">
                        {{ $decision ? number_format((int) $request->items_sum_approved_qty) : '—' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($request->status)
                            <flux:badge size="sm" :color="$request->status->color()">
                                {{ $request->status->label() }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($decision)
                            {{ $decision->decidedBy?->name ?? '—' }}
                            <flux:text size="sm">{{ $decision->decided_at?->format('M d, Y h:i A') }}</flux:text>
                        @else
                            <flux:text inline>—</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        @if ($request->status === \App\Enums\RequisitionStatus::Pending)
                            <flux:button size="xs" variant="primary" icon="clipboard-document-check"
                                wire:click="review({{ $request->id }})">
                                Review
                            </flux:button>
                        @else
                            <flux:button size="xs" variant="ghost" icon="eye" wire:click="review({{ $request->id }})">
                                View
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="clipboard-document-check" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">
                                {{ $status === 'pending' && $search === '' ? 'No requests awaiting approval' : 'No requests found' }}
                            </flux:heading>
                            <flux:text size="sm">
                                {{ $status === 'pending' && $search === '' ? 'New requests will appear here once submitted.' : 'Try a different search or filter.' }}
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Review / decision modal --}}
    <flux:modal name="review-request" class="w-[1000px] max-w-[95vw]">
        @if ($reviewing = $this->reviewing)
            @php
                $pending = $reviewing->status === \App\Enums\RequisitionStatus::Pending;
                $decision = $reviewing->latestApproval;
            @endphp

            <div class="space-y-6">
                <div class="pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $reviewing->request_number }}</flux:heading>
                        @if ($reviewing->status)
                            <flux:badge size="sm" :color="$reviewing->status->color()">{{ $reviewing->status->label() }}</flux:badge>
                        @endif
                    </div>
                    <flux:text class="mt-1">
                        Submitted by {{ $reviewing->requester?->name ?? '—' }}
                        on {{ $reviewing->submitted_at?->format('M d, Y h:i A') }}
                    </flux:text>
                </div>

                <div>
                    <flux:text size="sm">Purpose</flux:text>
                    <flux:text variant="strong">{{ $reviewing->purpose }}</flux:text>
                </div>

                @if ($decision)
                    <flux:callout :icon="$decision->decision === \App\Enums\ApprovalDecision::Approved ? 'check-circle' : 'x-circle'"
                        :color="$decision->decision?->color()">
                        <flux:callout.heading>
                            {{ $decision->decision?->label() }} by {{ $decision->decidedBy?->name ?? '—' }}
                            on {{ $decision->decided_at?->format('M d, Y h:i A') }}
                        </flux:callout.heading>
                        @if ($decision->remarks)
                            <flux:callout.text>{{ $decision->remarks }}</flux:callout.text>
                        @endif
                    </flux:callout>
                @endif

                @error('form.requisition_id')
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.text>{{ $message }}</flux:callout.text>
                    </flux:callout>
                @enderror

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="end">Requested</flux:table.column>
                        <flux:table.column align="end">Available*</flux:table.column>
                        @if ($pending)
                            <flux:table.column align="end">On Hand Now</flux:table.column>
                            <flux:table.column align="end">Committed</flux:table.column>
                            <flux:table.column align="end">Free</flux:table.column>
                            <flux:table.column align="end" class="w-36">Approve</flux:table.column>
                        @else
                            <flux:table.column align="end">Approved</flux:table.column>
                            <flux:table.column align="end">Released</flux:table.column>
                            <flux:table.column align="end">Outstanding</flux:table.column>
                        @endif
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($reviewing->items as $line)
                            @php $stock = $this->stock[$line->inventory_item_id] ?? ['on_hand' => 0, 'committed' => 0, 'free' => 0]; @endphp

                            <flux:table.row :key="'review-line-'.$line->id">
                                <flux:table.cell>
                                    <flux:text variant="strong" class="font-medium">{{ $line->inventoryItem?->name ?? '—' }}</flux:text>
                                    <flux:text size="sm">
                                        {{ $line->inventoryItem?->item_code }} · {{ $line->inventoryItem?->unit }}
                                    </flux:text>
                                </flux:table.cell>

                                <flux:table.cell align="end" variant="strong">{{ number_format($line->requested_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format($line->available_qty) }}</flux:table.cell>

                                @if ($pending)
                                    <flux:table.cell align="end">{{ number_format($stock['on_hand']) }}</flux:table.cell>
                                    <flux:table.cell align="end">{{ number_format($stock['committed']) }}</flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:text inline :color="$stock['free'] < $line->requested_qty ? 'amber' : null"
                                            class="font-medium">
                                            {{ number_format($stock['free']) }}
                                        </flux:text>
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <flux:input type="number" size="sm" min="0"
                                            max="{{ min($line->requested_qty, $stock['free']) }}"
                                            wire:model="form.approved.{{ $line->id }}"
                                            aria-label="Approved quantity for {{ $line->inventoryItem?->name }}" />
                                    </flux:table.cell>
                                @else
                                    <flux:table.cell align="end" variant="strong">
                                        {{ $line->approved_qty === null ? '—' : number_format($line->approved_qty) }}
                                    </flux:table.cell>
                                    <flux:table.cell align="end">{{ number_format($line->released_qty) }}</flux:table.cell>
                                    <flux:table.cell align="end">{{ number_format($line->outstanding_qty) }}</flux:table.cell>
                                @endif
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <flux:text size="sm">
                    * Stock on hand when the request was submitted.
                    @if ($pending)
                        Committed = approved for other requests but not yet released. Free = on hand − committed.
                    @endif
                </flux:text>

                @if ($pending)
                    <flux:error name="form.approved" :deep="false" />

                    <flux:textarea wire:model="form.remarks" label="Remarks" rows="2"
                        placeholder="Optional when approving, required when rejecting" />

                    <flux:separator variant="subtle" />

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <flux:button variant="ghost" size="sm" icon="arrow-path" wire:click="fillMaximum">
                            Fill maximum
                        </flux:button>

                        <div class="flex gap-2">
                            <flux:button variant="danger" icon="x-mark" wire:click="reject"
                                wire:confirm="Reject request {{ $reviewing->request_number }}? This cannot be undone.">
                                Reject
                            </flux:button>

                            <flux:button variant="primary" icon="check" wire:click="approve">Approve</flux:button>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end">
                        <flux:modal.close>
                            <flux:button variant="ghost">Close</flux:button>
                        </flux:modal.close>
                    </div>
                @endif
            </div>
        @endif
    </flux:modal>
</flux:card>
