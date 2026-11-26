@blaze(fold: true, unsafe: ['header'])
{{--
Loading state for a card with a heading and a table:
<x-skeleton.table :rows="5" :columns="4" heading="Recent activity" description="Latest entries…" class="lg:col-span-2" />
heading: true (skeleton line), false (no heading area) or a string (real heading text). A `header` slot replaces the heading area.
For a table inside an existing card, use <x-skeleton.table-rows> instead.
--}}
@props(['rows' => 5, 'columns' => 4, 'heading' => true, 'description' => null, 'action' => true])

<flux:card {{ $attributes }}>
    <flux:skeleton.group animate="pulse">
        @isset($header)
            {{ $header }}
        @elseif ($heading !== false)
            <x-skeleton.heading :heading="$heading" :description="$description" :action="$action" />
        @endisset

        <x-skeleton.table-rows :rows="$rows" :columns="$columns" />
    </flux:skeleton.group>
</flux:card>
