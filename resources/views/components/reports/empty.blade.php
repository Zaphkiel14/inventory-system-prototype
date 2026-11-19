{{-- Empty state for a report card: <x-reports.empty icon="truck" message="No deliveries." class="h-40" /> --}}
@props(['icon' => 'inbox', 'message' => 'No data for this period.'])

<div {{ $attributes->class('flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-200 text-center dark:border-white/10') }}>
    <flux:icon :name="$icon" variant="outline" class="size-6 text-zinc-400" />
    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ $message }}</flux:text>
</div>
