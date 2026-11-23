<flux:card variant="default">

    <div class="mb-4">
        <flux:heading size="xl" level="2">Requisition Requests</flux:heading>
        <flux:text class="mt-1">
            Requests submitted from the public welcome page. They go to approval, then approved quantities are released at issuance.
        </flux:text>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm"
                placeholder="Search request / tracking no., purpose, item, requester, designation"
                wire:model.live.debounce.500ms="search" />
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:select size="sm" class="md:w-48" wire:model.live="status">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach (\App\Enums\RequisitionStatus::cases() as $option)
                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->requests">
        <flux:table.columns>
            <flux:table.column>Request No.</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column>Requested By</flux:table.column>
            <flux:table.column>Purpose</flux:table.column>
            <flux:table.column align="end">Items</flux:table.column>
            <flux:table.column align="end">Total Qty</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->requests as $request)
                <flux:table.row :key="$request->id">
                    <flux:table.cell variant="strong" class="whitespace-nowrap font-medium">
                        {{ $request->request_number }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $request->submitted_at?->format('M d, Y') }}
                        <flux:text size="sm">{{ $request->submitted_at?->format('h:i A') }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $request->requester_name }}
                        @if ($request->designation)
                            <flux:text size="sm" class="max-w-56 truncate" :title="$request->designation">{{ $request->designation }}</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell class="max-w-64">
                        <flux:text class="truncate" :title="$request->purpose">{{ $request->purpose }}</flux:text>
                    </flux:table.cell>

                    <flux:table.cell align="end">{{ number_format($request->items_count) }}</flux:table.cell>
                    <flux:table.cell align="end">{{ number_format((int) $request->items_sum_requested_qty) }}</flux:table.cell>

                    <flux:table.cell>
                        @if ($request->status)
                            <flux:badge size="sm" :color="$request->status->color()">
                                {{ $request->status->label() }}
                            </flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end" class="whitespace-nowrap">
                        <flux:button size="xs" variant="ghost" icon="eye" wire:click="view({{ $request->id }})">
                            View
                        </flux:button>

                        @if ($request->isCancellableBy(auth()->user()))
                            <flux:button size="xs" variant="ghost" color="red" icon="x-mark"
                                wire:click="cancel({{ $request->id }})"
                                wire:confirm="Cancel request {{ $request->request_number }}? This cannot be undone.">
                                Cancel
                            </flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="clipboard-document-list" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">No requests found</flux:heading>
                            <flux:text size="sm">
                                {{ $search !== '' || $status !== '' ? 'Try a different search or filter.' : 'Submitted requests will appear here.' }}
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Request details --}}
    <flux:modal name="view-request" class="w-[900px] max-w-[95vw]">
        @if ($viewing = $this->viewing)
            <div class="space-y-6">
                <div class="pr-8">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $viewing->request_number }}</flux:heading>

                        @if ($viewing->status)
                            <flux:badge size="sm" :color="$viewing->status->color()">{{ $viewing->status->label() }}</flux:badge>
                        @endif
                    </div>

                    <flux:text class="mt-1">
                        Submitted by {{ $viewing->requester_name }}
                        on {{ $viewing->submitted_at?->format('M d, Y h:i A') }}
                        · Tracking no. <span class="font-mono">{{ $viewing->tracking_code }}</span>
                    </flux:text>
                </div>

                @include('livewire.requisitions.partials.requester-details', ['requisition' => $viewing])

                @if ($decision = $viewing->latestApproval)
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

                @if ($viewing->cancelled_at)
                    <flux:callout icon="x-circle" variant="secondary">
                        <flux:callout.text>
                            Cancelled on {{ $viewing->cancelled_at->format('M d, Y h:i A') }}.
                        </flux:callout.text>
                    </flux:callout>
                @endif

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Item</flux:table.column>
                        <flux:table.column align="end">Requested</flux:table.column>
                        <flux:table.column align="end">Available*</flux:table.column>
                        <flux:table.column align="end">Approved</flux:table.column>
                        <flux:table.column align="end">Released</flux:table.column>
                        <flux:table.column align="end">Outstanding</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($viewing->items as $line)
                            <flux:table.row :key="'line-'.$line->id">
                                <flux:table.cell>
                                    <flux:text variant="strong" class="font-medium">{{ $line->inventoryItem?->name ?? '—' }}</flux:text>
                                    <flux:text size="sm">
                                        {{ $line->inventoryItem?->item_code }} · {{ $line->inventoryItem?->unit }}
                                    </flux:text>
                                </flux:table.cell>
                                <flux:table.cell align="end" variant="strong">{{ number_format($line->requested_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format($line->available_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    @if ($line->approved_qty === null)
                                        <flux:text inline size="sm">Pending</flux:text>
                                    @else
                                        {{ number_format($line->approved_qty) }}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">{{ number_format($line->released_qty) }}</flux:table.cell>
                                <flux:table.cell align="end">{{ number_format($line->outstanding_qty) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <flux:text size="sm">* Stock on hand when the request was submitted.</flux:text>

                <div class="flex justify-end gap-2">
                    @if ($viewing->isCancellableBy(auth()->user()))
                        <flux:button variant="danger" icon="x-mark" wire:click="cancel({{ $viewing->id }})"
                            wire:confirm="Cancel request {{ $viewing->request_number }}? This cannot be undone.">
                            Cancel Request
                        </flux:button>
                    @endif

                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</flux:card>
