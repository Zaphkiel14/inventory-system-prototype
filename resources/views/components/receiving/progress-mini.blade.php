@props(['received', 'ordered'])

@php
    $percent = $ordered > 0 ? round(min(100, max(0, $received / $ordered * 100)), 2) : 0;
@endphp

<div class="w-32 max-w-full space-y-1">
    <div role="img" aria-label="{{ $received }} of {{ $ordered }} received"
        class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
        <div class="h-full rounded-full bg-emerald-500 transition-[width] motion-reduce:transition-none"
            style="width: {{ $percent }}%"></div>
    </div>
    <flux:text size="sm" class="tabular-nums">{{ number_format($received) }} of {{ number_format($ordered) }} received</flux:text>
</div>
