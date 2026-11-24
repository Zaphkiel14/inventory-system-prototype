<flux:card>

    <div class="mb-4">
        <flux:heading size="xl" level="2">Suppliers</flux:heading>
        <flux:text class="mt-1">Manage the suppliers you receive stock from.</flux:text>
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center">
        <div class="md:flex-1">
            <flux:input icon="magnifying-glass" size="sm" placeholder="Search name, contact person, phone, email"
                wire:model.live.debounce.500ms="search" />
        </div>

        <div class="flex items-center gap-2">
            <flux:select size="sm" class="md:w-48" wire:model.live="status">
                <flux:select.option value="all">All statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </flux:select>

            <flux:button size="sm" icon="plus" x-on:click="$dispatch('add-supplier')">
                Add Supplier
            </flux:button>
        </div>
    </div>

    <flux:table :paginate="$this->suppliers">
        <flux:table.columns>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">
                Name
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'contact_person'" :direction="$sortDirection"
                wire:click="sort('contact_person')">
                Contact
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'stock_receipts_count'" :direction="$sortDirection"
                wire:click="sort('stock_receipts_count')">
                Deliveries
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'stock_receipts_max_received_date'"
                :direction="$sortDirection" wire:click="sort('stock_receipts_max_received_date')">
                Last Delivery
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection"
                wire:click="sort('is_active')">
                Status
            </flux:table.column>

            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->suppliers as $supplier)
                <flux:table.row :key="$supplier->id">
                    <flux:table.cell variant="strong">
                        {{ $supplier->name }}
                        @if ($supplier->address)
                            <flux:text size="sm" class="font-normal truncate max-w-64">{{ $supplier->address }}</flux:text>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div>{{ $supplier->contact_person ?? '—' }}</div>
                        <flux:text size="sm">
                            {{ collect([$supplier->contact, $supplier->email])->filter()->join(' · ') }}
                        </flux:text>
                    </flux:table.cell>

                    <flux:table.cell>{{ number_format($supplier->stock_receipts_count) }}</flux:table.cell>

                    <flux:table.cell :variant="$supplier->stock_receipts_max_received_date ? null : 'muted'">
                        {{ $supplier->stock_receipts_max_received_date
                            ? \Illuminate\Support\Carbon::parse($supplier->stock_receipts_max_received_date)->format('M d, Y')
                            : 'Never' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$supplier->is_active ? 'green' : 'zinc'">
                            {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex justify-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"
                                    aria-label="Actions for {{ $supplier->name }}" />

                                <flux:menu>
                                    <flux:menu.item icon="pencil-square"
                                        x-on:click="$dispatch('edit-supplier', { id: {{ $supplier->id }} })">
                                        Edit
                                    </flux:menu.item>

                                    <flux:menu.item :icon="$supplier->is_active ? 'no-symbol' : 'check-circle'"
                                        wire:click="toggleActive({{ $supplier->id }})">
                                        {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
                                    </flux:menu.item>

                                    <flux:menu.separator />

                                    <flux:menu.item icon="trash" variant="danger"
                                        wire:click="delete({{ $supplier->id }})"
                                        wire:confirm="Delete {{ $supplier->name }}? This cannot be undone.">
                                        Delete
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">
                        <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                            <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />
                            <flux:heading size="sm">No suppliers found</flux:heading>
                            <flux:text size="sm" class="text-zinc-500">
                                Add a supplier to start receiving stock from them.
                            </flux:text>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:inventory.supplier-form />
</flux:card>
