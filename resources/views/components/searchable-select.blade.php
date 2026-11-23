{{--
resources/views/components/searchable-select.blade.php

Single-select dropdown with a search box (free alternative to Flux Pro's
searchable listbox). Same Alpine pattern as x-pillbox.

Usage:
<x-searchable-select wire:model="form.area_id" label="Designation" :options="$areas" />

Options: a [value => label] map, or a list of ['value' => ..., 'label' => ...].
Props: label, description, placeholder, empty-message, disabled.
--}}
@props([
    'options' => [],
    'label' => null,
    'description' => null,
    'placeholder' => 'Select...',
    'emptyMessage' => 'No results found.',
    'disabled' => false,
])

@php
    $model = $attributes->wire('model');
    $name = $model->value();
    $live = $model->hasModifier('live');

    $normalized = collect($options)
        ->map(fn ($option, $key) => is_array($option)
            ? ['value' => $option['value'], 'label' => (string) $option['label']]
            : ['value' => $key, 'label' => (string) $option])
        ->values();

    $hasError = $name && $errors->has($name);
@endphp

<flux:field {{ $attributes->whereDoesntStartWith('wire:model') }}>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <div class="relative w-full" x-data="{
            open: false,
            search: '',
            active: 0,
            options: @js($normalized),
            selected: $wire.$entangle('{{ $name }}'{{ $live ? ', true' : '' }}),
            disabled: @js($disabled),

            get current() {
                return this.options.find(o => String(o.value) === String(this.selected)) ?? null
            },
            get filtered() {
                const q = this.search.trim().toLowerCase()
                return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options
            },
            choose(option) {
                if (this.disabled || !option) return
                this.selected = option.value
                this.search = ''
                this.open = false
            },
            show() {
                if (this.disabled) return
                this.open = true
                this.$nextTick(() => this.$refs.search?.focus())
            },
            move(step) {
                if (!this.open) return this.show()
                const count = this.filtered.length
                if (!count) return
                this.active = (this.active + step + count) % count
                this.$nextTick(() => this.$refs.list
                    .querySelector(`[data-index='${this.active}']`)
                    ?.scrollIntoView({ block: 'nearest' }))
            },
        }" x-init="$watch('search', () => active = 0)" x-on:click.outside="open = false"
        x-on:keydown.escape="if (open) { $event.stopPropagation(); open = false }">

        {{-- Trigger --}}
        <button type="button" x-on:click="open ? open = false : show()"
            x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
            @if ($disabled) disabled @endif
            @class([
                'flex h-10 w-full items-center gap-2 rounded-lg border bg-white ps-3 pe-2 text-start text-sm shadow-xs',
                'border-zinc-200 border-b-zinc-300/80 dark:border-white/10 dark:bg-white/10',
                'border-red-500 dark:border-red-400' => $hasError,
                'cursor-not-allowed opacity-50' => $disabled,
            ])
            role="combobox" x-bind:aria-expanded="open">
            <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-100" x-show="current" x-text="current?.label"></span>
            <span class="min-w-0 flex-1 truncate text-zinc-400" x-show="! current">{{ $placeholder }}</span>
            <flux:icon.chevron-down variant="mini" class="size-4 shrink-0 text-zinc-400 transition-transform"
                x-bind:class="open && 'rotate-180'" />
        </button>

        {{-- Panel --}}
        <div x-show="open" x-cloak x-transition.opacity.duration.100ms
            class="absolute inset-x-0 top-full z-60 mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-600 dark:bg-zinc-700">
            <div class="border-b border-zinc-200 p-2 dark:border-zinc-600">
                <input x-ref="search" type="text" x-model="search" autocomplete="off"
                    placeholder="{{ __('Search...') }}"
                    x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                    x-on:keydown.enter.prevent="choose(filtered[active])"
                    class="w-full rounded-md border-0 bg-zinc-100 px-2 py-1.5 text-sm text-zinc-700 outline-none placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100" />
            </div>

            <ul x-ref="list" role="listbox" class="max-h-60 overflow-y-auto p-1">
                <template x-for="(option, index) in filtered" :key="option.value">
                    <li role="option" x-bind:data-index="index" x-bind:aria-selected="current?.value === option.value"
                        x-on:mousedown.prevent x-on:click="choose(option)" x-on:mouseenter="active = index"
                        class="flex cursor-pointer select-none items-center gap-2 rounded-md px-2.5 py-2 text-sm text-zinc-700 dark:text-zinc-100"
                        x-bind:class="active === index && 'bg-zinc-100 dark:bg-zinc-600'">
                        <span class="flex size-4 shrink-0 items-center justify-center text-(--color-accent-content)">
                            <flux:icon.check variant="micro" class="size-4"
                                x-show="current && String(current.value) === String(option.value)" />
                        </span>
                        <span class="min-w-0 truncate" x-text="option.label"></span>
                    </li>
                </template>
            </ul>

            <div class="px-3 py-5 text-center text-sm text-zinc-500 dark:text-zinc-400" x-show="! filtered.length">
                {{ $emptyMessage }}
            </div>
        </div>
    </div>

    @if ($name)
        <flux:error :name="$name" />
    @endif
</flux:field>
