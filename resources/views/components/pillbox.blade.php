{{--
resources/views/components/pillbox.blade.php

Reusable multi-select "pillbox" (free alternative to Flux Pro's pillbox).

Usage:
<x-pillbox wire:model="form.category_ids" label="Categories" placeholder="Select categories..."
    :options="$this->categories->pluck('name', 'id')" />

Options: a [value => label] map, or a list of ['value' => ..., 'label' => ...].
Props: label, description, placeholder, search-placeholder, empty-message,
max, clearable, searchable, disabled. Works with wire:model(.live).
--}}
@props([
'options' => [],
'label' => null,
'description' => null,
'placeholder' => 'Select...',
'searchPlaceholder' => 'Search...',
'emptyMessage' => 'No results found.',
'max' => null,
'clearable' => false,
'searchable' => true,
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

@once
<style>
    [x-cloak] {
        display: none !important;
    }

    .pb {
        position: relative;
        width: 100%;
    }

    /* ---- field ---- */
    .pb-field {
        position: relative;
        box-sizing: border-box;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        width: 100%;
        min-height: 40px;
        padding: 5px 34px 5px 8px;
        border: 1px solid #e4e4e7;
        border-bottom-color: #d4d4d8;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgb(0 0 0 / .05);
        cursor: text;
        transition: border-color .15s;
    }

    .pb-field:focus-within {
        border-color: #a1a1aa;
    }

    .pb-field.is-invalid {
        border-color: #ef4444;
    }

    .pb-field.is-disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* ---- pills ---- */
    .pb-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        max-width: 100%;
        height: 26px;
        padding: 0 4px 0 8px;
        border-radius: 6px;
        background: #f4f4f5;
        color: #3f3f46;
        font-size: 14px;
        font-weight: 500;
        line-height: 1;
    }

    .pb-pill-x {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        border-radius: 4px;
        color: #71717a;
        cursor: pointer;
    }

    .pb-pill-x:hover {
        background: rgb(0 0 0 / .08);
        color: #18181b;
    }

    /* ---- search input ---- */
    .pb-input,
    .pb-input:focus {
        flex: 1 1 80px;
        min-width: 80px;
        height: 26px;
        margin: 0;
        padding: 0;
        border: 0 !important;
        border-radius: 0;
        outline: none !important;
        box-shadow: none !important;
        background: transparent !important;
        color: #3f3f46;
        font-size: 14px;
    }

    .pb-input::placeholder {
        color: #a1a1aa;
        opacity: 1;
    }

    /* ---- right-side controls ---- */
    .pb-controls {
        position: absolute;
        top: 0;
        right: 8px;
        bottom: 0;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .pb-icon-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        padding: 0;
        border: 0;
        background: transparent;
        color: #a1a1aa;
        cursor: pointer;
    }

    .pb-icon-btn:hover {
        color: #3f3f46;
    }

    .pb-chevron {
        display: inline-flex;
        transition: transform .15s;
    }

    .pb-chevron.is-open {
        transform: rotate(180deg);
    }

    /* ---- dropdown ---- */
    .pb-panel {
        position: absolute;
        z-index: 60;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 4px;
        overflow: hidden;
        border: 1px solid var(--color-zinc-200, #e5e5e5);
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / .1), 0 4px 6px -4px rgb(0 0 0 / .1);
    }

    .pb-list {
        max-height: 240px;
        margin: 0;
        padding: 4px;
        overflow-y: auto;
        list-style: none;
        scrollbar-width: thin;
    }

    .pb-option {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        padding: 8px 10px;
        border-radius: 6px;
        color: #3f3f46;
        font-size: 14px;
        line-height: 1.25;
        cursor: pointer;
        user-select: none;
    }

    .pb-option.is-active {
        background: var(--color-zinc-100, #f5f5f5);
    }

    .pb-option.is-selected {
        font-weight: 500;
    }

    .pb-option.is-disabled {
        opacity: .4;
        cursor: not-allowed;
    }

    .pb-check {
        display: inline-flex;
        flex: none;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        color: var(--color-accent-content, #059669);
    }

    .pb-empty {
        padding: 20px 10px;
        text-align: center;
        font-size: 14px;
        color: #71717a;
    }

    /* ---- dark mode ---- */
    .dark .pb-field {
        border-color: rgb(255 255 255 / .1);
        background: rgb(255 255 255 / .1);
    }

    .dark .pb-field:focus-within {
        border-color: rgb(255 255 255 / .3);
    }

    .dark .pb-field.is-invalid {
        border-color: #f87171;
    }

    .dark .pb-pill {
        background: rgb(255 255 255 / .15);
        color: #f4f4f5;
    }

    .dark .pb-pill-x {
        color: #d4d4d8;
    }

    .dark .pb-pill-x:hover {
        background: rgb(255 255 255 / .2);
        color: #fff;
    }

    .dark .pb-input,
    .dark .pb-input:focus {
        color: #f4f4f5;
    }

    .dark .pb-input::placeholder {
        color: #a1a1aa;
    }

    .dark .pb-icon-btn:hover {
        color: #fff;
    }

    .dark .pb-panel {
        border-color: var(--color-zinc-600, #525252);
        background: var(--color-zinc-700, #404040);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / .4), 0 4px 6px -4px rgb(0 0 0 / .4);
    }

    .dark .pb-option {
        color: var(--color-zinc-100, #f5f5f5);
    }

    .dark .pb-option.is-active {
        background: var(--color-zinc-600, #525252);
    }

    .dark .pb-check {
        color: var(--color-accent-content, #34d399);
    }

    .dark .pb-empty {
        color: #a1a1aa;
    }
</style>
@endonce

<flux:field {{ $attributes->whereDoesntStartWith('wire:model') }}>
    @if ($label)
    <flux:label>{{ $label }}</flux:label>
    @endif

    @if ($description)
    <flux:description>{{ $description }}</flux:description>
    @endif

    <div class="pb" x-data="{
            open: false,
            search: '',
            active: 0,
            options: @js($normalized),
            selected: $wire.$entangle('{{ $name }}'{{ $live ? ', true' : '' }}),
            max: @js($max),
            disabled: @js($disabled),
            placeholder: @js($placeholder),
            searchPlaceholder: @js($searchPlaceholder),

            get list() {
                return Array.isArray(this.selected) ? this.selected : []
            },
            get pills() {
                return this.list
                    .map(v => this.options.find(o => String(o.value) === String(v)))
                    .filter(Boolean)
            },
            get filtered() {
                const q = this.search.trim().toLowerCase()
                return q
                    ? this.options.filter(o => o.label.toLowerCase().includes(q))
                    : this.options
            },
            get atMax() {
                return this.max !== null && this.list.length >= this.max
            },
            has(value) {
                return this.list.some(s => String(s) === String(value))
            },
            isDisabled(option) {
                return this.atMax && !this.has(option.value)
            },
            toggle(option) {
                if (this.disabled || !option || this.isDisabled(option)) return
                this.selected = this.has(option.value)
                    ? this.list.filter(s => String(s) !== String(option.value))
                    : [...this.list, option.value]
                this.search = ''
            },
            remove(value) {
                if (this.disabled) return
                this.selected = this.list.filter(s => String(s) !== String(value))
            },
            clear() {
                if (this.disabled) return
                this.selected = []
                this.search = ''
            },
            show() {
                if (!this.disabled) this.open = true
            },
            move(step) {
                if (!this.open) return this.show()
                const count = this.filtered.length
                if (!count) return
                this.active = (this.active + step + count) % count
                this.$nextTick(() => {
                    this.$refs.list
                        .querySelector(`[data-index='${this.active}']`)
                        ?.scrollIntoView({ block: 'nearest' })
                })
            },
        }" x-init="$watch('search', () => active = 0)" x-on:click.outside="open = false">
        {{-- Field: pills + search input + controls --}}
        <div x-on:click="$refs.input.focus(); show()" @class([ 'pb-field' , 'is-invalid'=> $hasError,
            'is-disabled' => $disabled,
            ])
            >
            <template x-for="pill in pills" :key="pill.value">
                <span class="pb-pill">
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                        x-text="pill.label"></span>

                    <span role="button" tabindex="-1" class="pb-pill-x" x-on:click.stop="remove(pill.value)"
                        x-bind:aria-label="'Remove ' + pill.label">
                        <flux:icon.x-mark variant="micro" class="size-3" />
                    </span>
                </span>
            </template>

            <input x-ref="input" type="text" role="combobox" autocomplete="off" aria-autocomplete="list"
                class="pb-input" x-bind:aria-expanded="open" x-model="search"
                x-bind:placeholder="pills.length ? searchPlaceholder : placeholder" @if (! $searchable) readonly @endif
                @if ($disabled) disabled @endif x-on:focus="show()" x-on:input="open = true"
                x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)"
                x-on:keydown.enter.prevent="open && toggle(filtered[active])"
                x-on:keydown.escape="if (open) { $event.stopPropagation(); open = false }"
                x-on:keydown.tab="open = false"
                x-on:keydown.backspace="if (search === '' && list.length) remove(list[list.length - 1])" />

            <div class="pb-controls">
                @if ($clearable)
                <button type="button" tabindex="-1" class="pb-icon-btn" x-show="list.length" x-cloak
                    x-on:click.stop="clear()" aria-label="Clear selection">
                    <flux:icon.x-mark variant="micro" />
                </button>
                @endif

                <button type="button" tabindex="-1" class="pb-icon-btn"
                    x-on:click.stop="open ? open = false : ($refs.input.focus(), show())" aria-label="Toggle options">
                    <span class="pb-chevron" x-bind:class="open && 'is-open'">
                        <flux:icon.chevron-down variant="mini" class="size-4" />
                    </span>
                </button>
            </div>
        </div>

        {{-- Options --}}
        <div class="pb-panel" x-show="open" x-cloak x-transition.opacity.duration.100ms>
            <ul x-ref="list" role="listbox" aria-multiselectable="true" class="pb-list">
                <template x-for="(option, index) in filtered" :key="option.value">
                    <li role="option" class="pb-option" x-bind:data-index="index"
                        x-bind:aria-selected="has(option.value)" x-bind:aria-disabled="isDisabled(option)" x-bind:class="{
                            'is-active': active === index,
                            'is-selected': has(option.value),
                            'is-disabled': isDisabled(option),
                        }" x-on:mousedown.prevent x-on:click="toggle(option)" x-on:mouseenter="active = index">
                        <span class="pb-check">
                            <span x-show="has(option.value)" style="display: inline-flex;">
                                <flux:icon.check variant="micro" class="size-4" />
                            </span>
                        </span>

                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                            x-text="option.label"></span>
                    </li>
                </template>
            </ul>

            <div class="pb-empty" x-show="! filtered.length">{{ $emptyMessage }}</div>
        </div>
    </div>

    @if ($name)
    <flux:error :name="$name" />
    @endif
</flux:field>