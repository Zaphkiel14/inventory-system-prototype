{{--
resources/views/components/datetime-picker.blade.php

Date and time picker (free alternative to Flux Pro's date and time pickers).
The logic lives in resources/js/datetime-picker.js.

Usage:
<x-datetime-picker wire:model="form.received_date" label="Received Date & Time" />
<x-datetime-picker wire:model="form.expiration_date" label="Expiry" :time="false" min="2026-01-01" clearable />

The value is "YYYY-MM-DDTHH:mm" ("YYYY-MM-DD" with :time="false"), the same
format <input type="datetime-local"> sends. "Today" and "Now" follow the app
timezone (config('app.timezone')).

Props: label, description, placeholder, min, max (date or date-time strings),
minute-step, time (show the time fields, default true), clearable, disabled,
week-start (0 = Sunday), size ("sm" for filter bars), align ("end" opens the
panel from the right edge, for fields near the right of the screen).
Without a label, pass aria-label so screen readers can name the field.

For dates only, use <x-date-picker>, which wraps this component.
--}}
@props([
    'label' => null,
    'description' => null,
    'placeholder' => null,
    'min' => null,
    'max' => null,
    'minuteStep' => 1,
    'time' => true,
    'clearable' => false,
    'disabled' => false,
    'weekStart' => 0,
    'size' => null,
    'align' => 'start',
])

@php
    $name = $attributes->wire('model')->value();
    $hasError = $name && $errors->has($name);
    $placeholder ??= $time ? __('Select date and time') : __('Select date');
    $minuteStep = max(1, min(60, (int) $minuteStep));
    $ariaLabel = $attributes->get('aria-label') ?? $label ?? $placeholder;

    $config = [
        'time' => (bool) $time,
        'min' => $min,
        'max' => $max,
        'minuteStep' => $minuteStep,
        'weekStart' => (int) $weekStart,
        'disabled' => (bool) $disabled,
        'timeZone' => config('app.timezone'),
        'locale' => str_replace('_', '-', app()->getLocale()),
    ];
@endphp

<flux:field {{ $attributes->whereDoesntStartWith('wire:model')->except('aria-label') }}>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif

    <div class="relative w-full" x-data="datetimePicker(@js($config))" x-modelable="value"
        {{ $attributes->whereStartsWith('wire:model') }} x-on:click.outside="close()"
        x-on:keydown.escape="if (open) { $event.preventDefault(); $event.stopPropagation(); back() }"
        data-datetime-picker>

        {{-- Trigger --}}
        <button type="button" x-ref="trigger" x-on:click="toggle()"
            x-on:keydown.arrow-down.prevent="show()"
            @if ($disabled) disabled @endif
            @class([
                'flex w-full items-center gap-2 border bg-white ps-3 pe-2 text-start text-sm shadow-xs',
                'h-8 rounded-md' => $size === 'sm',
                'h-10 rounded-lg' => $size !== 'sm',
                'border-zinc-200 border-b-zinc-300/80 dark:border-white/10 dark:bg-white/10',
                'border-red-500 dark:border-red-400' => $hasError,
                'cursor-not-allowed opacity-50' => $disabled,
            ])
            aria-haspopup="dialog" x-bind:aria-expanded="open"
            x-bind:aria-label="@js($ariaLabel) + (value ? ', ' + display : '')">
            <flux:icon.calendar-days variant="mini" class="size-4 shrink-0 text-zinc-400" />
            <span class="min-w-0 flex-1 truncate text-zinc-700 dark:text-zinc-100" x-show="value" x-text="display"></span>
            <span class="min-w-0 flex-1 truncate text-zinc-400" x-show="! value">{{ $placeholder }}</span>
            <flux:icon.chevron-down variant="mini" class="size-4 shrink-0 text-zinc-400 transition-transform"
                x-bind:class="open && 'rotate-180'" />
        </button>

        {{-- Panel --}}
        <div x-show="open" x-cloak x-transition.opacity.duration.100ms role="dialog"
            aria-label="{{ $time ? __('Choose date and time') : __('Choose date') }}"
            class="absolute {{ $align === 'end' ? 'end-0' : 'start-0' }} top-full z-60 mt-1 w-80 max-w-[calc(100vw-2rem)] space-y-3 rounded-lg border border-zinc-200 bg-white p-3 shadow-lg dark:border-zinc-600 dark:bg-zinc-700">

            {{-- Navigation: the heading opens the month view, then the year view --}}
            <div class="flex items-center justify-between gap-2">
                <flux:button size="sm" variant="ghost" icon="chevron-left" type="button" x-on:click="prev()"
                    x-bind:aria-label="prevLabel" />

                <flux:button size="sm" variant="ghost" icon:trailing="chevron-down" type="button"
                    x-on:click="toggleMode()" x-bind:aria-label="heading + ', ' + headingHint" data-datetime-heading>
                    <span x-text="heading" aria-live="polite"></span>
                </flux:button>

                <flux:button size="sm" variant="ghost" icon="chevron-right" type="button" x-on:click="next()"
                    x-bind:aria-label="nextLabel" />
            </div>

            {{-- Months --}}
            <div x-show="mode === 'months'" x-ref="months" class="grid grid-cols-3 gap-1.5">
                <template x-for="option in months" :key="option.month">
                    <button type="button" x-on:click="pickMonth(option)" x-bind:disabled="option.isDisabled"
                        x-bind:aria-label="option.longLabel" x-bind:aria-pressed="option.isSelected"
                        x-bind:data-selected="option.isSelected" x-bind:data-current="option.isCurrent"
                        x-text="option.label"
                        class="h-11 rounded-md text-sm outline-none transition-colors focus-visible:ring-2 focus-visible:ring-(--color-accent) disabled:cursor-not-allowed disabled:opacity-30"
                        x-bind:class="{
                            'bg-(--color-accent) text-(--color-accent-foreground) font-medium': option.isSelected,
                            'text-zinc-800 hover:bg-zinc-100 dark:text-white dark:hover:bg-zinc-600': ! option.isSelected,
                            'font-semibold underline decoration-2 underline-offset-4': option.isCurrent && ! option.isSelected,
                        }"></button>
                </template>
            </div>

            {{-- Years --}}
            <div x-show="mode === 'years'" x-ref="years" class="grid grid-cols-3 gap-1.5">
                <template x-for="option in years" :key="option.year">
                    <button type="button" x-on:click="pickYear(option)" x-bind:disabled="option.isDisabled"
                        x-bind:aria-pressed="option.isSelected"
                        x-bind:data-selected="option.isSelected" x-bind:data-current="option.isCurrent"
                        x-text="option.year"
                        class="h-11 rounded-md text-sm tabular-nums outline-none transition-colors focus-visible:ring-2 focus-visible:ring-(--color-accent) disabled:cursor-not-allowed disabled:opacity-30"
                        x-bind:class="{
                            'bg-(--color-accent) text-(--color-accent-foreground) font-medium': option.isSelected,
                            'text-zinc-800 hover:bg-zinc-100 dark:text-white dark:hover:bg-zinc-600': ! option.isSelected,
                            'font-semibold underline decoration-2 underline-offset-4': option.isCurrent && ! option.isSelected,
                        }"></button>
                </template>
            </div>

            {{-- Calendar --}}
            <div x-show="mode === 'days'" role="grid" x-ref="grid" x-on:keydown="onGridKey($event)">
                <div role="row" class="grid grid-cols-7 pb-1">
                    <template x-for="weekday in weekdays" :key="weekday.long">
                        <div role="columnheader" class="text-center text-xs font-medium text-zinc-400"
                            x-bind:aria-label="weekday.long" x-text="weekday.short"></div>
                    </template>
                </div>

                <div class="grid grid-cols-7 gap-y-0.5">
                    <template x-for="cell in cells" :key="cell.key">
                        <button type="button" role="gridcell" x-bind:data-key="cell.key"
                            x-bind:tabindex="cell.isFocused ? 0 : -1" x-bind:disabled="cell.isDisabled"
                            x-bind:aria-selected="cell.isSelected" x-bind:aria-label="cell.label"
                            x-bind:aria-current="cell.isToday ? 'date' : null"
                            x-on:click="pick(cell)" x-on:focus="focused = cell.key"
                            class="mx-auto flex size-9 items-center justify-center rounded-md text-sm tabular-nums outline-none transition-colors focus-visible:ring-2 focus-visible:ring-(--color-accent) disabled:cursor-not-allowed disabled:opacity-30"
                            x-bind:class="{
                                'bg-(--color-accent) text-(--color-accent-foreground) font-medium': cell.isSelected,
                                'hover:bg-zinc-100 dark:hover:bg-zinc-600': ! cell.isSelected && ! cell.isDisabled,
                                'text-zinc-800 dark:text-white': cell.inMonth && ! cell.isSelected,
                                'text-zinc-400 dark:text-zinc-500': ! cell.inMonth && ! cell.isSelected,
                                'font-semibold underline decoration-2 underline-offset-4': cell.isToday && ! cell.isSelected,
                            }"
                            x-text="cell.day"></button>
                    </template>
                </div>
            </div>

            {{-- Time --}}
            @if ($time)
                <div class="flex items-center gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-600">
                    <flux:icon.clock variant="mini" class="size-4 shrink-0 text-zinc-400" />

                    <flux:select size="sm" class="min-w-0 flex-1" aria-label="{{ __('Hour') }}"
                        x-bind:value="hour12" x-on:change="setTime({ hour12: $event.target.value })">
                        @foreach (range(1, 12) as $hour)
                            <option value="{{ $hour }}">{{ $hour }}</option>
                        @endforeach
                    </flux:select>

                    <span class="text-zinc-400">:</span>

                    <flux:select size="sm" class="min-w-0 flex-1" aria-label="{{ __('Minute') }}"
                        x-bind:value="minuteValue" x-on:change="setTime({ minute: $event.target.value })">
                        @foreach (range(0, 59, $minuteStep) as $minute)
                            <option value="{{ str_pad($minute, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($minute, 2, '0', STR_PAD_LEFT) }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select size="sm" class="min-w-0 flex-1" aria-label="{{ __('AM or PM') }}"
                        x-bind:value="meridiem" x-on:change="setTime({ meridiem: $event.target.value })">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </flux:select>
                </div>
            @endif

            {{-- Footer --}}
            <div class="flex items-center gap-1 border-t border-zinc-200 pt-3 dark:border-zinc-600">
                <flux:button size="sm" variant="ghost" type="button" x-on:click="setNow()">
                    {{ $time ? __('Now') : __('Today') }}
                </flux:button>

                @if ($clearable)
                    <flux:button size="sm" variant="ghost" type="button" x-on:click="clear()">
                        {{ __('Clear') }}
                    </flux:button>
                @endif

                <flux:spacer />

                <flux:button size="sm" variant="primary" type="button" x-on:click="close(true)">
                    {{ __('Done') }}
                </flux:button>
            </div>
        </div>
    </div>

    @if ($name)
        <flux:error :name="$name" />
    @endif
</flux:field>
