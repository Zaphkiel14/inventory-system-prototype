<flux:card variant="default">

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="2">Inventory Items</flux:heading>
            <flux:text class="mt-1">Manage your inventory items.</flux:text>
        </div>

        <div class="flex items-center gap-2">


            <flux:input class="w-64" icon="magnifying-glass" size="sm" placeholder="Search Items"
                wire:model.live.debounce.500ms="search" />
            <flux:modal.trigger name="create-item">
                <flux:button size="sm" icon="plus">Add Item</flux:button>
            </flux:modal.trigger>
            @livewire('inventory.item-create-form')
            @livewire('inventory.item-edit-form')

        </div>
    </div>
    <flux:table :paginate="$this->items">
        <flux:table.columns >
            <flux:table.column >
                Item Code
            </flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">
                Name
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'unit'" :direction="$sortDirection"
                wire:click="sort('unit')">
                Unit
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'requires_expiry'" :direction="$sortDirection"
                wire:click="sort('requires_expiry')">
                Requires Expiry
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'minimum_stock'" :direction="$sortDirection"
                wire:click="sort('minimum_stock')">
                Minimum Stock
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'reorder_level'" :direction="$sortDirection"
                wire:click="sort('reorder_level')">
                Reorder Level
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection"
                wire:click="sort('is_active')">
                Status
            </flux:table.column>

            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->items as $item)
            <flux:table.row :key="$item->id">
                <flux:table.cell >
                    {{ $item->item_code }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $item->name }}
                </flux:table.cell>

                <flux:table.cell variant="muted">
                    {{ $item->unit }}
                </flux:table.cell>

                <flux:table.cell variant="muted">
                    {{ $item->requires_expiry ? 'Yes' : 'No' }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $item->minimum_stock }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $item->reorder_level }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" :color="$item->is_active ? 'green' : 'zinc'">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"
                            aria-label="Actions for {{ $item->name }}" />

                        <flux:menu>
                            <flux:menu.item icon="eye" wire:click="view({{ $item->id }})">
                                View
                            </flux:menu.item>
                            <flux:menu.item icon="pencil-square"
                                wire:click="$dispatch('edit-inventory-item', { itemId: {{ $item->id }} })">
                                Edit
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="9">
                    <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                        <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />

                        <flux:heading size="sm">
                            No items found
                        </flux:heading>

                        <flux:text size="sm" class="text-zinc-500">
                            There are currently no items to display.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Item details (read-only) --}}
    <flux:modal name="view-item" class="w-[640px] max-w-[95vw]">
        @if ($viewed = $this->viewing)
            @php
                $available = (int) ($viewed->available_qty ?? 0);
                $committed = (int) ($viewed->committed_qty ?? 0);
                $free = max(0, $available - $committed);
            @endphp

            <div class="space-y-6">
                {{-- Header --}}
                <div class="pr-8">
                    <flux:text size="sm">{{ $viewed->item_code }}</flux:text>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ $viewed->name }}</flux:heading>
                        <flux:badge size="sm" :color="$viewed->is_active ? 'green' : 'zinc'">
                            {{ $viewed->is_active ? 'Active' : 'Inactive' }}
                        </flux:badge>
                        @if ($viewed->stock_status)
                            <flux:tooltip :content="$viewed->stock_status->description()">
                                <div class="inline-flex">
                                    <flux:badge size="sm" :color="$viewed->stock_status->color()">
                                        {{ $viewed->stock_status->label() }}
                                    </flux:badge>
                                </div>
                            </flux:tooltip>
                        @endif
                    </div>
                </div>

                {{-- Stock on hand --}}
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        ['Available', $available, 'In usable, unexpired batches'],
                        ['Committed', $committed, 'Approved but not yet released'],
                        ['Free', $free, 'Available minus committed'],
                        ['Usable Batches', $viewed->usable_batches_count, number_format($viewed->inventory_batches_count).' batches received in total'],
                    ] as [$label, $value, $hint])
                        <flux:card size="sm" class="space-y-1">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:heading size="lg" class="tabular-nums">
                                {{ number_format($value) }}
                                @if ($label !== 'Usable Batches')
                                    <flux:text inline size="sm">{{ $viewed->unit }}</flux:text>
                                @endif
                            </flux:heading>
                            <flux:text size="sm">{{ $hint }}</flux:text>
                        </flux:card>
                    @endforeach
                </div>

                {{-- Settings --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3">
                    @foreach ([
                        'Unit' => $viewed->unit,
                        'Requires Expiry' => $viewed->requires_expiry ? 'Yes' : 'No',
                        'Issue Order' => \App\Services\Inventory\BatchAllocator::methodFor($viewed).($viewed->requires_expiry ? ' · earliest expiry first' : ' · oldest batch first'),
                        'Minimum Stock' => number_format($viewed->minimum_stock),
                        'Reorder Level' => number_format($viewed->reorder_level),
                        'Created' => $viewed->created_at?->format('M d, Y h:i A'),
                        'Last Updated' => $viewed->updated_at?->format('M d, Y h:i A'),
                    ] as $label => $value)
                        <div class="min-w-0">
                            <flux:text size="sm">{{ $label }}</flux:text>
                            <flux:text variant="strong" class="break-words">{{ $value ?? '—' }}</flux:text>
                        </div>
                    @endforeach
                </div>

                <flux:separator variant="subtle" />

                {{-- Categories --}}
                <div class="space-y-2">
                    <flux:heading>
                        Categories
                        <flux:text inline size="sm">({{ $viewed->inventoryCategory->count() }})</flux:text>
                    </flux:heading>

                    @if ($viewed->inventoryCategory->isNotEmpty())
                        <div class="divide-y divide-zinc-800/10 dark:divide-white/10">
                            @foreach ($viewed->inventoryCategory as $category)
                                <div class="flex items-start justify-between gap-3 py-2" wire:key="view-cat-{{ $category->id }}">
                                    <div class="min-w-0">
                                        <flux:text variant="strong" class="font-medium">{{ $category->name }}</flux:text>
                                        @if ($category->description)
                                            <flux:text size="sm" class="break-words">{{ $category->description }}</flux:text>
                                        @endif
                                    </div>
                                    @unless ($category->is_active)
                                        <flux:badge size="sm" color="zinc">Inactive</flux:badge>
                                    @endunless
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text size="sm">This item isn't in any category.</flux:text>
                    @endif
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                    <flux:button icon="pencil-square" wire:click="editViewing">Edit</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

</flux:card>