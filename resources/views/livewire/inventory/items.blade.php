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
        <flux:table.columns sticky class="bg-white dark:bg-zinc-700">
            <flux:table.column sticky class="bg-white dark:bg-zinc-700">
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

            <flux:table.column sortable :sorted="$sortBy === 'allocation_method'" :direction="$sortDirection"
                wire:click="sort('allocation_method')">
                Allocation Method
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
                <flux:table.cell sticky variant="strong" class="bg-white dark:bg-zinc-700">
                    {{ $item->item_code }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $item->name }}
                </flux:table.cell>

                <flux:table.cell variant="muted">
                    {{ $item->unit }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ str($item->allocation_method)->replace('_', ' ')}}
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
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                        <flux:menu>
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

</flux:card>