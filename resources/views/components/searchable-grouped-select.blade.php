{{--
resources/views/components/searchable-grouped-select.blade.php

Single-select dropdown with a search box AND hierarchical option groups
(searchable-select's pattern + <optgroup>-style headers). Free alternative to
Flux Pro's searchable listbox.

Usage:
<x-searchable-grouped-select wire:model="form.area_id" label="Designation" :groups="$areaGroups" />

Groups: a list of ['label' => 'Finance Office', 'options' => [value => label]].
Prefix an option label with "— " per nesting level (e.g. "— Accounting Section")
to indent it under its parent; the prefix is stripped and turned into padding.
Searching matches option labels and group names (a group-name match shows the
whole group); groups with no matches are hidden.
Props: label, description, placeholder, empty-message, disabled.
--}}
@props([
    'groups' => [],
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

    $normalized = collect($groups)
        ->map(fn ($group) => [
            'label' => (string) $group['label'],
            'options' => collect($group['options'])
                ->map(function ($optionLabel, $value) use ($group) {
                    $clean = preg_replace('/^(— )+/u', '', (string) $optionLabel);
                    $depth = (int) ((mb_strlen((string) $optionLabel) - mb_strlen($clean)) / 2);

                    return [
                        'value' => $value,
                        'label' => $clean,
                        'depth' => $depth,
                        // What the trigger shows once picked: keeps the parent for context.
                        'display' => $clean === $group['label'] ? $clean : $clean.' · '.$group['label'],
                    ];
                })
                ->values(),
        ])
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
            groups: @js($normalized),
            selected: $wire.$entangle('{{ $name }}'{{ $live ? ', true' : '' }}),
            disabled: @js($disabled),

            get all() {
                return this.groups.flatMap(g => g.options)
            },
            get current() {
                return this.all.find(o => String(o.value) === String(this.selected)) ?? null
            },
            // Groups narrowed by the search; each option carries its flat index for keyboard nav.
            get visible() {
                const q = this.search.trim().toLowerCase()
                let index = 0

                return this.groups
                    .map(group => {
                        const groupMatches = q && group.label.toLowerCase().includes(q)
                        const options = group.options
                            .filter(o => ! q || groupMatches || o.label.toLowerCase().includes(q))
                            .map(o => ({ ...o, index: index++ }))

                        return { label: group.label, options }
                    })
                    .filter(group => group.options.length)
            },
            get filtered() {
                return this.visible.flatMap(g => g.options)
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
            <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-100" x-show="current" x-text="current?.display"></span>
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

            <div x-ref="list" role="listbox" class="max-h-60 overflow-y-auto p-1">
                <template x-for="(group, groupIndex) in visible" :key="groupIndex + group.label">
                    <div role="group" x-bind:aria-label="group.label">
                        <div class="sticky top-0 z-10 bg-white px-2.5 pb-1 pt-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400"
                            x-text="group.label"></div>

                        <template x-for="option in group.options" :key="option.value">
                            <div role="option" x-bind:data-index="option.index"
                                x-bind:aria-selected="current && String(current.value) === String(option.value)"
                                x-on:mousedown.prevent x-on:click="choose(option)" x-on:mouseenter="active = option.index"
                                class="flex cursor-pointer select-none items-center gap-2 rounded-md py-2 pe-2.5 text-sm text-zinc-700 dark:text-zinc-100"
                                x-bind:style="`padding-inline-start: ${0.625 + option.depth * 1}rem`"
                                x-bind:class="active === option.index && 'bg-zinc-100 dark:bg-zinc-600'">
                                <span class="flex size-4 shrink-0 items-center justify-center text-(--color-accent-content)">
                                    <flux:icon.check variant="micro" class="size-4"
                                        x-show="current && String(current.value) === String(option.value)" />
                                </span>
                                <span class="min-w-0 truncate" x-text="option.label"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="px-3 py-5 text-center text-sm text-zinc-500 dark:text-zinc-400" x-show="! filtered.length">
                {{ $emptyMessage }}
            </div>
        </div>
    </div>

    @if ($name)
        <flux:error :name="$name" />
    @endif
</flux:field>
