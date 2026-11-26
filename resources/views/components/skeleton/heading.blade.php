@blaze(fold: true, unsafe: ['slot'])
{{--
Card heading area for skeletons, matching the real card header (mb-4, heading + description, action on the right).
<x-skeleton.heading heading="Low stock" description="Items at or below…" />
heading: string renders the real text, true renders a skeleton line. A default slot replaces the whole left side.
description: string renders the real text, null renders a skeleton line. action: false hides the right-hand skeleton.
--}}
@props(['heading' => true, 'description' => null, 'action' => true])

<div {{ $attributes->class('mb-4 flex items-start justify-between gap-4') }}>
    @if ($slot->isNotEmpty())
        <div>{{ $slot }}</div>
    @else
        <div class="flex w-full max-w-md flex-col gap-1">
            @if (is_string($heading))
                <flux:heading>{{ $heading }}</flux:heading>
            @else
                <flux:skeleton.line size="lg" class="w-36" />
            @endif

            @if (is_string($description))
                <flux:text size="sm">{{ $description }}</flux:text>
            @else
                <flux:skeleton.line class="w-3/4" />
            @endif
        </div>
    @endif

    @if ($action)
        <flux:skeleton.line class="w-14 shrink-0" />
    @endif
</div>
