<flux:card variant="default">

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="2">Receiving Inventory Stock</flux:heading>
            <flux:text class="mt-1">Manage incoming inventory item batches.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:input class="w-64" icon="magnifying-glass" size="sm" placeholder="Search receipts"
                wire:model.live.debounce.500ms="search" />
            <flux:modal.trigger name="create-receiving">
                <flux:button size="sm" icon="plus">Receive Stock</flux:button>
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
            <flux:table.column>Batches</flux:table.column>
            <flux:table.column>Total Qty</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column><span class="sr-only">Actions</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->receipts as $receipt)
            <flux:table.row :key="$receipt->id">
                <flux:table.cell variant="strong">{{ $receipt->reference_number }}</flux:table.cell>
                <flux:table.cell>{{ $receipt->supplier?->name }}</flux:table.cell>
                <flux:table.cell variant="muted">{{ $receipt->purchase_order_number ?? '—' }}</flux:table.cell>
                <flux:table.cell class="whitespace-nowrap">
                    {{ $receipt->received_date->format('M d, Y') }}
                    <flux:text size="sm">{{ $receipt->received_date->format('h:i A') }}</flux:text>
                </flux:table.cell>
                <flux:table.cell>{{ $receipt->inventory_batches_count }}</flux:table.cell>
                <flux:table.cell>{{ number_format($receipt->inventory_batches_sum_received_qty ?? 0) }}
                </flux:table.cell>
                <flux:table.cell>
                    <flux:badge size="sm" :color="$receipt->status->color()">
                        {{ $receipt->status->label() }}
                    </flux:badge>
                </flux:table.cell>
                <flux:table.cell align="end">
                    <flux:dropdown position="bottom" align="end">
                        <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal"
                            aria-label="Actions for {{ $receipt->reference_number }}" />

                        <flux:menu>
                            <flux:menu.item icon="cube" wire:navigate
                                :href="route('inventory.stocks', ['receipt' => $receipt->id])">
                                View stock batches ({{ $receipt->inventory_batches_count }})
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="8">
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
</flux:card>