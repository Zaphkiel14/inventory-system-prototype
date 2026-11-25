@props(['timeline'])

@php
    $isClosed = $timeline['status'] === \App\Enums\ReceiptStatus::Closed;
    $lines = $timeline['lines'];
    $deliveries = $timeline['deliveries'];
    $anyLater = collect($lines)->contains(fn ($l) => ($l['later'] ?? 0) > 0);
    $complete = $timeline['status'] === \App\Enums\ReceiptStatus::Complete && ($timeline['outstanding'] ?? 0) === 0;

    $earlierBar = 'bg-amber-500 dark:bg-amber-400';
    $laterBar = 'bg-amber-300 dark:bg-amber-600';
    $nowBar = $complete ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-sky-500 dark:bg-sky-400';
    $nowBadge = $complete ? 'green' : 'sky';
    $pct = fn ($n, $total) => $total > 0 ? round(min(100, max(0, $n / $total * 100)), 2) : 0;
@endphp

<div class="grid gap-6 md:grid-cols-2">
    {{-- Progress per item --}}
    <div class="min-w-0 space-y-4">
        <flux:heading>By item</flux:heading>

        <flux:text size="sm" class="tabular-nums">
            {{ number_format($timeline['received']) }} of {{ number_format($timeline['ordered']) }} units received
        </flux:text>

        <div class="space-y-4">
            @foreach ($lines as $line)
                @php
                    $later = $line['later'] ?? 0;
                    $got = $line['earlier'] + $line['now'] + $later;
                    $aria = $line['item']?->name.': '.$line['earlier'].' received earlier, '
                        .$line['now'].' this receipt, '
                        .($later > 0 ? $later.' received later, ' : '')
                        .$line['outstanding'].' outstanding, of '.$line['ordered'].' ordered';
                @endphp
                <div class="space-y-1.5" wire:key="timeline-line-{{ $loop->index }}">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                        <flux:text variant="strong" class="min-w-0 truncate font-medium">{{ $line['item']?->name ?? '—' }}</flux:text>
                        <flux:text size="sm" class="tabular-nums">
                            @if ($line['outstanding'] === 0)
                                {{ number_format($got) }} of {{ number_format($line['ordered']) }} {{ $line['item']?->unit }} · complete
                            @else
                                {{ number_format($got) }} of {{ number_format($line['ordered']) }} {{ $line['item']?->unit }} · {{ number_format($line['outstanding']) }} outstanding{{ $isClosed && ($line['closed_short'] ?? 0) > 0 ? ' · no longer expected' : '' }}
                            @endif
                        </flux:text>
                    </div>

                    <div role="img" aria-label="{{ $aria }}"
                        class="flex h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full {{ $earlierBar }} transition-[width] motion-reduce:transition-none"
                            style="width: {{ $pct($line['earlier'], $line['ordered']) }}%"></div>
                        <div class="h-full {{ $nowBar }} transition-[width] motion-reduce:transition-none"
                            style="width: {{ $pct($line['now'], $line['ordered']) }}%"></div>
                        @if ($later > 0)
                            <div class="h-full {{ $laterBar }} transition-[width] motion-reduce:transition-none"
                                style="width: {{ $pct($later, $line['ordered']) }}%"></div>
                        @endif
                        @if ($isClosed && ($line['closed_short'] ?? 0) > 0)
                            <div class="h-full bg-[repeating-linear-gradient(45deg,transparent,transparent_3px,rgb(161_161_170/0.5)_3px,rgb(161_161_170/0.5)_6px)] transition-[width] motion-reduce:transition-none"
                                style="width: {{ $pct($line['closed_short'], $line['ordered']) }}%"></div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full {{ $earlierBar }}"></span>
                <flux:text size="sm" inline>received earlier</flux:text>
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full {{ $nowBar }}"></span>
                <flux:text size="sm" inline>this receipt</flux:text>
            </span>
            @if ($anyLater)
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full {{ $laterBar }}"></span>
                    <flux:text size="sm" inline>received later</flux:text>
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                <flux:text size="sm" inline>outstanding</flux:text>
            </span>
        </div>
    </div>

    {{-- Deliveries (files live in the Attachments tab) --}}
    <div class="min-w-0 space-y-4">
        <flux:heading>Deliveries</flux:heading>

        <ol class="relative space-y-3 border-s border-zinc-200 ps-4 dark:border-zinc-700">
            @foreach ($deliveries as $delivery)
                <li wire:key="timeline-delivery-{{ $delivery['id'] }}" class="relative">
                    <span @class([
                        'absolute -start-[1.3rem] top-1.5 size-2.5 rounded-full ring-4 ring-white dark:ring-zinc-800',
                        $nowBar => $delivery['is_current'],
                        $earlierBar => ! $delivery['is_current'],
                    ])></span>
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                        <span class="font-mono text-sm font-medium text-zinc-800 dark:text-white">{{ $delivery['reference_number'] }}</span>
                        @if ($delivery['is_current'])
                            <flux:badge size="sm" :color="$nowBadge">this receipt</flux:badge>
                        @endif
                        <flux:text size="sm" inline>{{ $delivery['received_date']->format('M d, Y h:i A') }}</flux:text>
                    </div>
                    <flux:text size="sm" class="line-clamp-2 tabular-nums">
                        @if (count($delivery['items']))
                            {{ collect($delivery['items'])->map(fn ($i) => number_format($i['qty']).' '.$i['unit'].' '.$i['name'])->implode(', ') }}
                        @else
                            Received nothing
                        @endif
                    </flux:text>
                </li>
            @endforeach
        </ol>
    </div>
</div>
