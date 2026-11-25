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
                <flux:table.cell>
                    @if ($receipt->status === \App\Enums\ReceiptStatus::Closed && $receipt->closed_reason)
                        <flux:tooltip :content="$receipt->closed_reason">
                            <flux:badge size="sm" :color="$receipt->status->color()">
                                {{ $receipt->status->label() }}
                            </flux:badge>
                        </flux:tooltip>
                    @elseif ($progress = ($this->progressByReceipt[$receipt->id] ?? null))
                        <flux:tooltip :content="number_format($progress['received']).' of '.number_format($progress['ordered']).' received'">
                            <flux:badge size="sm" :color="$receipt->status->color()">
                                {{ $receipt->status->label() }}
                            </flux:badge>
                        </flux:tooltip>
                    @else
                        <flux:badge size="sm" :color="$receipt->status->color()">
                            {{ $receipt->status->label() }}
                        </flux:badge>
                    @endif
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
            @include('livewire.inventory.partials.receipt-details', ['viewed' => $viewed])
        @endif
    </flux:modal>
</flux:card>
