<section class="w-full">
    @include('partials.configuration-heading')

    <flux:heading level="2" class="sr-only">{{ __('Sticker Profiles') }}</flux:heading>

    <x-configurations.layout :heading="__('Sticker Profiles')"
        :subheading="__('Sticker sheet layouts in millimetres, used to print property tags')">

        <div class="mb-4 flex items-center justify-end gap-2">
            <flux:button size="sm" icon="plus" x-on:click="$dispatch('add-sticker-profile')">
                Add Sticker Profile
            </flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Page</flux:table.column>
                <flux:table.column>Grid</flux:table.column>
                <flux:table.column>Tag Size</flux:table.column>
                <flux:table.column align="end">Prints</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->profiles as $profile)
                    <flux:table.row :key="$profile->id">
                        <flux:table.cell variant="strong">
                            <div class="flex flex-wrap items-center gap-2">
                                {{ $profile->name }}
                                @if ($profile->is_default)
                                    <flux:badge size="sm" color="green">Default</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap tabular-nums">
                            {{ number_format((float) $profile->page_width_mm, 1) }} × {{ number_format((float) $profile->page_height_mm, 1) }} mm
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap">
                            {{ $profile->columns }} × {{ $profile->rows }}
                            <flux:text size="sm">{{ $profile->slots_per_sheet }} per sheet</flux:text>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-nowrap tabular-nums">
                            {{ number_format((float) $profile->tag_width_mm, 1) }} × {{ number_format((float) $profile->tag_height_mm, 1) }} mm
                        </flux:table.cell>

                        <flux:table.cell align="end">{{ number_format((int) $profile->prints_count) }}</flux:table.cell>

                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                @unless ($profile->is_default)
                                    <flux:button variant="ghost" size="sm" inset="top bottom" wire:click="makeDefault({{ $profile->id }})">
                                        Make default
                                    </flux:button>
                                @endunless

                                <flux:button variant="ghost" size="sm" icon="pencil-square" inset="top bottom"
                                    x-on:click="$dispatch('edit-sticker-profile', { id: {{ $profile->id }} })"
                                    aria-label="Edit {{ $profile->name }}" />

                                <flux:button variant="ghost" size="sm" icon="trash" inset="top bottom"
                                    class="text-red-500 hover:text-red-600"
                                    wire:click="delete({{ $profile->id }})"
                                    wire:confirm="Delete {{ $profile->name }}? This cannot be undone."
                                    aria-label="Delete {{ $profile->name }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <div class="flex flex-col items-center justify-center gap-2 py-8 text-center">
                                <flux:icon name="squares-2x2" variant="outline" class="size-8 text-zinc-400" />
                                <flux:heading size="sm">No sticker profiles</flux:heading>
                                <flux:text size="sm">Add the layout of the sticker sheets you print tags on.</flux:text>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <livewire:configurations.sticker-profile-form />
    </x-configurations.layout>
</section>
