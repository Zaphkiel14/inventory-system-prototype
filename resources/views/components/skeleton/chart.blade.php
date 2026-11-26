{{--
Loading state for a chart card: <x-skeleton.chart height="h-64" heading="Stock movement" /> or <x-skeleton.chart donut />
heading: true (skeleton line), false (no heading area) or a string (real heading text). A `header` slot replaces the heading area.
--}}
@props(['height' => 'h-64', 'heading' => true, 'description' => null, 'action' => true, 'donut' => false])

<flux:card {{ $attributes }}>
    <flux:skeleton.group animate="pulse">
        @isset($header)
            {{ $header }}
        @elseif ($heading !== false)
            <x-skeleton.heading :heading="$heading" :description="$description" :action="$action" />
        @endisset

        @if ($donut)
            <div class="flex {{ $height }} flex-col items-center justify-center gap-4" aria-hidden="true">
                <flux:skeleton class="aspect-square h-3/4 rounded-full" />
                <flux:skeleton.line class="w-2/3" />
            </div>
        @else
            <flux:skeleton class="{{ $height }} w-full rounded-lg" aria-hidden="true" />
        @endif
    </flux:skeleton.group>
</flux:card>
