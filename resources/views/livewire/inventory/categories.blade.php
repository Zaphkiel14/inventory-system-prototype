<flux:card>

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="2">Inventory Categories</flux:heading>
            <flux:text class="mt-1">Manage your inventory Categories.</flux:text>
        </div>

        <div class="flex items-center gap-2">


            <flux:input class="w-64" icon="magnifying-glass" size="sm" placeholder="Search Categories"
                wire:model.live.debounce.500ms="search" />
            <flux:modal.trigger name="create-category">
                <flux:button size="sm" icon="plus">Add Category</flux:button>
            </flux:modal.trigger>
            @livewire('inventory.category-create-form')

        </div>
    </div>
    <flux:table :paginate="$this->categories">
        <flux:table.columns sticky>
            <flux:table.column sticky sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                wire:click="sort('name')">
                Name
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection"
                wire:click="sort('description')">
                Description
            </flux:table.column>

            <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection"
                wire:click="sort('is_active')">
                Status
            </flux:table.column>

            <flux:table.column />
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->categories as $category)
            <flux:table.row :key="$category->id">
                <flux:table.cell sticky variant="strong">
                    {{ $category->name }}
                </flux:table.cell>

                <flux:table.cell>
                    {{ $category->description }}
                </flux:table.cell>

                <flux:table.cell>
                    <flux:badge size="sm" :color="$category->is_active ? 'green' : 'zinc'">
                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </flux:table.cell>

                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                </flux:table.cell>
            </flux:table.row>
            @empty
            <flux:table.row>
                <flux:table.cell colspan="9">
                    <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                        <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />

                        <flux:heading size="sm">
                            No categories found
                        </flux:heading>

                        <flux:text size="sm" class="text-zinc-500">
                            There are currently no categories to display.
                        </flux:text>
                    </div>
                </flux:table.cell>
            </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>