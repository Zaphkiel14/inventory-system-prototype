@props(['timeline', 'mode' => 'view'])

@php
    $isPreview = $mode === 'preview';
    $status = $timeline['status'];
    $isClosed = $status === \App\Enums\ReceiptStatus::Closed;
    $lines = $timeline['lines'];
    $deliveries = $timeline['deliveries'];
    $rootRef = $deliveries[0]['reference_number'] ?? '';
    $current = collect($deliveries)->firstWhere('is_current', true);
    $previewItems = $isPreview
        ? collect($lines)->filter(fn ($l) => $l['now'] > 0)->values()
        : collect();
    $anyLater = collect($lines)->contains(fn ($l) => ($l['later'] ?? 0) > 0);
    $complete = $status === \App\Enums\ReceiptStatus::Complete && ($timeline['outstanding'] ?? 0) === 0;

    // Colour choices live here only. Preview keeps the original palette.
    if ($isPreview) {
        $earlierBar = 'bg-emerald-500';
        $nowBar = 'bg-amber-400';
        $laterBar = 'bg-emerald-300 dark:bg-emerald-700';
        $nowBadge = 'amber';
        $nowRow = 'border-amber-400 bg-amber-50/60 dark:border-amber-500/60 dark:bg-amber-500/10';
    } else {
        $earlierBar = 'bg-amber-500 dark:bg-amber-400';
        $laterBar = 'bg-amber-300 dark:bg-amber-600';
        if ($complete) {
            $nowBar = 'bg-emerald-500 dark:bg-emerald-400';
            $nowBadge = 'green';
            $nowRow = 'border-emerald-500 bg-emerald-50/60 dark:border-emerald-400/60 dark:bg-emerald-500/10';
        } else {
            $nowBar = 'bg-sky-500 dark:bg-sky-400';
            $nowBadge = 'sky';
            $nowRow = 'border-sky-500 bg-sky-50/60 dark:border-sky-400/60 dark:bg-sky-500/10';
        }
    }
    $pct = fn ($n, $total) => $total > 0 ? round(min(100, max(0, $n / $total * 100)), 2) : 0;

    if ($isClosed) {
        $hint = 'No attachments needed to close a delivery.';
    } elseif ($isPreview || ($current && ! $current['is_root'])) {
        $hint = 'Attachments optional: files from '.$rootRef.' are reused.';
    } else {
        $hint = 'Attachments required on the first delivery.';
    }
@endphp

<div class="grid gap-6 md:grid-cols-2">
    {{-- Order progress --}}
    <div class="min-w-0 space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <flux:heading>Order Progress</flux:heading>
            @if ($status)
                <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
            @endif
        </div>

        <flux:text size="sm" class="tabular-nums">
            {{ number_format($timeline['received']) }} of {{ number_format($timeline['ordered']) }} units received
        </flux:text>

        <div class="space-y-4">
            @foreach ($lines as $line)
                @php
                    $later = $line['later'] ?? 0;
                    $got = $line['earlier'] + $line['now'] + $later;
                    $aria = $line['item']?->name.': '.$line['earlier'].' received earlier, '
                        .$line['now'].' '.($isPreview ? 'this delivery' : 'this receipt').', '
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
                <flux:text size="sm" inline>{{ $isPreview ? 'this delivery' : 'this receipt' }}</flux:text>
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

        @if ($timeline['closed_reason'])
            <flux:callout icon="information-circle" color="zinc">
                <flux:callout.heading>Closed short</flux:callout.heading>
                <flux:callout.text>{{ $timeline['closed_reason'] }}</flux:callout.text>
            </flux:callout>
        @endif
    </div>

    {{-- Deliveries --}}
    <div class="min-w-0 space-y-4">
        <flux:heading>Deliveries</flux:heading>

        <ol class="space-y-3">
            @foreach ($deliveries as $delivery)
                <li wire:key="timeline-delivery-{{ $delivery['id'] }}"
                    @class([
                        'flex gap-3 rounded-lg border p-3',
                        $nowRow => $delivery['is_current'],
                        'border-zinc-200 dark:border-zinc-700' => ! $delivery['is_current'],
                    ])>
                    <span @class([
                        'mt-1.5 size-2.5 shrink-0 rounded-full',
                        $nowBar => $delivery['is_current'],
                        $earlierBar => ! $delivery['is_current'],
                    ])></span>

                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-medium text-zinc-800 dark:text-white">{{ $delivery['reference_number'] }}</span>
                            @if ($delivery['is_current'])
                                <flux:badge size="sm" :color="$nowBadge">this receipt</flux:badge>
                            @endif
                        </div>
                        @unless ($delivery['is_root'])
                            <flux:text size="sm">↳ follow-up of {{ $rootRef }}</flux:text>
                        @endunless
                        <flux:text size="sm">{{ $delivery['received_date']->format('M d, Y h:i A') }}</flux:text>

                        <flux:text size="sm" class="tabular-nums">
                            @if (count($delivery['items']))
                                Received {{ collect($delivery['items'])->map(fn ($i) => number_format($i['qty']).' '.$i['unit'].' '.$i['name'])->implode(', ') }}
                            @else
                                Received nothing
                            @endif
                        </flux:text>

                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach ($delivery['files'] as $file)
                                @if ($file['url'])
                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener"
                                        class="inline-flex max-w-full items-center gap-1 rounded-md border border-zinc-200 px-2 py-0.5 text-xs text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-white/5">
                                        <flux:icon :name="$file['pdf'] ? 'document-text' : ($file['image'] ? 'photo' : 'paper-clip')" class="size-3.5 shrink-0" />
                                        <span class="truncate">{{ $file['name'] }}</span>
                                    </a>
                                @else
                                    <span class="inline-flex max-w-full items-center gap-1 rounded-md border border-red-300 px-2 py-0.5 text-xs text-red-600 dark:border-red-500/50 dark:text-red-400">
                                        <span class="truncate">{{ $file['name'] }}</span> · missing
                                    </span>
                                @endif
                            @endforeach
                            @if ($delivery['inherits_files'])
                                <span class="inline-flex max-w-full items-center rounded-md border border-dashed border-zinc-300 px-2 py-0.5 text-xs text-zinc-500 dark:border-zinc-600 dark:text-zinc-400">
                                    uses files from {{ $rootRef }}
                                </span>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach

            @if ($isPreview)
                <li class="flex gap-3 rounded-lg border border-dashed border-amber-400 p-3 dark:border-amber-500/60">
                    <span class="mt-1.5 size-2.5 shrink-0 rounded-full border-2 border-amber-400"></span>
                    <div class="min-w-0 flex-1 space-y-1">
                        <flux:text variant="strong" class="font-medium">New delivery (this form)</flux:text>
                        <flux:text size="sm" class="tabular-nums">
                            @if ($previewItems->isNotEmpty())
                                Received {{ $previewItems->map(fn ($l) => number_format($l['now']).' '.$l['item']?->unit.' '.$l['item']?->name)->implode(', ') }}
                            @else
                                Received nothing
                            @endif
                        </flux:text>
                    </div>
                </li>
            @endif
        </ol>

        <flux:text size="sm">{{ $hint }}</flux:text>
    </div>
</div>
