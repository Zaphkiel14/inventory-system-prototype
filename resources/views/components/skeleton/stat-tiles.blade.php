{{-- Loading state for a row of stat tiles: <x-skeleton.stat-tiles :count="4" /> --}}
@props(['count' => 4])

<div {{ $attributes->class('grid gap-4 sm:grid-cols-2 xl:grid-cols-4') }} aria-hidden="true">
    @foreach (range(1, $count) as $tile)
        <flux:card size="sm" class="flex h-full flex-col gap-2">
            <flux:skeleton.group animate="shimmer" class="flex flex-col gap-2">
                <div class="flex items-center justify-between gap-2">
                    <flux:skeleton.line class="w-28" />
                    <flux:skeleton class="size-4 rounded" />
                </div>
                <flux:skeleton class="h-8 w-16" />
                <flux:skeleton.line class="w-40" />
            </flux:skeleton.group>
        </flux:card>
    @endforeach
</div>
