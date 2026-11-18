<div>
    <label class="block text-sm font-medium text-zinc-800 dark:text-zinc-200 mb-2">
        Categories
    </label>

    <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false"
        class="relative">
        {{-- Selected categories and search input --}}
        <div class="flex flex-wrap items-center gap-1.5 min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-2.5 py-2 shadow-sm transition
                   focus-within:border-zinc-400 focus-within:ring-2 focus-within:ring-zinc-200
                   dark:border-zinc-700 dark:bg-zinc-900 dark:focus-within:border-zinc-500 dark:focus-within:ring-zinc-700"
            x-on:click="$refs.searchInput.focus(); open = true">
            {{-- Selected pills --}}
            @foreach ($this->selectedCategories as $category)
            <span wire:key="selected-category-{{ $category->id }}" class="inline-flex items-center gap-1 rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700
                           dark:bg-zinc-800 dark:text-zinc-200">
                {{ $category->name }}

                <button type="button" wire:click.stop="removeCategory({{ $category->id }})"
                    class="rounded-sm text-zinc-500 hover:text-red-600 dark:hover:text-red-400"
                    aria-label="Remove {{ $category->name }}">
                    &times;
                </button>
            </span>
            @endforeach

            {{-- Search --}}
            <input x-ref="searchInput" type="text" wire:model.live.debounce.300ms="search" x-on:focus="open = true"
                placeholder="{{ count($value) ? 'Add another category...' : 'Search categories...' }}" class="min-w-28 flex-1 border-0 bg-transparent p-1 text-sm text-zinc-900 outline-none
                       placeholder:text-zinc-400 focus:ring-0
                       dark:text-zinc-100 dark:placeholder:text-zinc-500" />
        </div>

        {{-- Dropdown --}}
        <div x-cloak x-show="open" x-transition.origin.top class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-lg border border-zinc-200
                   bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($this->categories as $category)
            @php
            $isSelected = in_array(
            $category->id,
            array_map('intval', $value),
            true
            );
            @endphp

            <button type="button" wire:key="category-option-{{ $category->id }}"
                wire:click="toggleCategory({{ $category->id }})" class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition
                           {{ $isSelected
                                ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800'
                           }}">
                <span>{{ $category->name }}</span>

                @if ($isSelected)
                <span class="text-zinc-600 dark:text-zinc-300">
                    ✓
                </span>
                @endif
            </button>
            @empty
            <div class="px-3 py-6 text-center text-sm text-zinc-500">
                No categories found.
            </div>
            @endforelse

            @if ($this->categories->count() === 50)
            <div class="border-t border-zinc-100 px-3 py-2 text-xs text-zinc-500 dark:border-zinc-800">
                Showing up to 50 results. Refine your search.
            </div>
            @endif
        </div>
    </div>

    @error('form.category_ids')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>