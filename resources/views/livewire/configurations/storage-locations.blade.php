<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Storage Location') }}</flux:heading>

    <x-configurations.layout :heading="__('Storage Locations')"
        :subheading=" __('Add/Update storage locations for easy logging of items')">

        <div class="flex items-center justify-between gap-2 mb-4">
            <flux:input class="max-w-64" icon="magnifying-glass" size="sm" placeholder="Search storage locations"
                wire:model.live.debounce.500ms="search" />

            <flux:button size="sm" icon="plus" x-on:click="$dispatch('add-storage-location')">
                Add Storage Location
            </flux:button>
        </div>

        <flux:table :paginate="$this->locations">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                    wire:click="sort('name')">
                    Name
                </flux:table.column>

                <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection"
                    wire:click="sort('type')">
                    Type
                </flux:table.column>

                <flux:table.column sortable :sorted="$sortBy === 'inventory_batches_count'"
                    :direction="$sortDirection" wire:click="sort('inventory_batches_count')">
                    Batches
                </flux:table.column>

                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->locations as $location)
                    <flux:table.row :key="$location->id">
                        <flux:table.cell variant="strong">{{ $location->name }}</flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $location->type }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>{{ $location->inventory_batches_count }}</flux:table.cell>

                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button variant="ghost" size="sm" icon="pencil-square" inset="top bottom"
                                    x-on:click="$dispatch('edit-storage-location', { id: {{ $location->id }} })"
                                    aria-label="Edit {{ $location->name }}" />

                                <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom"
                                    class="text-red-500 hover:text-red-600"
                                    wire:click="delete({{ $location->id }})"
                                    wire:confirm="Delete {{ $location->name }}? This cannot be undone."
                                    aria-label="Delete {{ $location->name }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                <flux:icon name="inbox" variant="outline" class="size-8 text-zinc-400" />
                                <flux:heading size="sm">No storage locations found</flux:heading>
                                <flux:text size="sm" class="text-zinc-500">
                                    There are currently no storage locations to display.
                                </flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <livewire:configurations.storage-location-form />
    </x-configurations.layout>
</section>
