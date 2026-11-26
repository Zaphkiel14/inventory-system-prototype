@blaze(fold: true, unsafe: ['header'])
{{--
Loading state for a list widget card: <x-skeleton.list-card :rows="5" heading="Low stock" description="Items at or below…" />
heading: true (skeleton line) or a string (real heading text). A `header` slot replaces the heading area.
--}}
@props(['rows' => 5, 'heading' => true, 'description' => null, 'action' => true])

<flux:card {{ $attributes }}>
    <flux:skeleton.group animate="pulse">
        @isset($header)
            {{ $header }}
        @else
            <x-skeleton.heading :heading="$heading" :description="$description" :action="$action" />
        @endisset

        <div class="divide-y divide-zinc-800/10 dark:divide-white/20" aria-hidden="true">
            @foreach (range(1, $rows) as $row)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <flux:skeleton.line class="w-1/2" />
                        <flux:skeleton.line class="w-1/4" />
                    </div>
                    <flux:skeleton.line class="w-16 shrink-0" />
                    <flux:skeleton class="h-5 w-14 shrink-0 rounded-md" />
                </div>
            @endforeach
        </div>
    </flux:skeleton.group>
</flux:card>
