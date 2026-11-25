{{--
Loading state for a row of stat tiles: <x-skeleton.stat-tiles :count="4" />
labels: optional list of real tile labels (e.g. ['Out of stock', 'Critical']); tiles without one get a skeleton line.
--}}
@props(['count' => 4, 'labels' => []])

<div {{ $attributes->class('grid gap-4 sm:grid-cols-2 xl:grid-cols-4') }}>
    @foreach (range(0, $count - 1) as $index)
        <flux:card size="sm" class="flex h-full flex-col gap-2">
            <flux:skeleton.group animate="shimmer" class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    @if (isset($labels[$index]))
                        <flux:text size="sm">{{ $labels[$index] }}</flux:text>
                    @else
                        <flux:skeleton.line class="w-28" aria-hidden="true" />
                    @endif
                    <flux:skeleton class="size-4 rounded" aria-hidden="true" />
                </div>
                <flux:skeleton class="h-8 w-16" aria-hidden="true" />
                <flux:skeleton.line class="w-40" aria-hidden="true" />
            </flux:skeleton.group>
        </flux:card>
    @endforeach
</div>
