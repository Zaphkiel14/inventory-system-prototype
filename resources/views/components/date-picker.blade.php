{{--
resources/views/components/date-picker.blade.php

Date picker (free alternative to Flux Pro's date picker). It is
<x-datetime-picker> with the time fields turned off, so both share one look,
one calendar and the same month and year views.

Usage:
<x-date-picker wire:model="form.expiration_date" label="Expiration" clearable />
<x-date-picker wire:model.live="from" size="sm" aria-label="From" align="end" />

The value is "YYYY-MM-DD", the same format <input type="date"> sends.

Props: label, description, placeholder, min, max, clearable, disabled,
week-start (0 = Sunday), size ("sm"), align ("end"), plus aria-label when
there is no label.
--}}
<x-datetime-picker :attributes="$attributes->merge(['time' => false])" />
