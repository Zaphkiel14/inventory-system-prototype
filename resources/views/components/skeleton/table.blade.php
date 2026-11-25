{{--
Loading state for a card with a heading and a table:
<x-skeleton.table :rows="5" :columns="4" heading="Recent activity" description="Latest entries…" class="lg:col-span-2" />
heading: true (skeleton line), false (no heading area) or a string (real heading text). A `header` slot replaces the heading area.
--}}
@props(['rows' => 5, 'columns' => 4, 'heading' => true, 'description' => null, 'action' => true])

<flux:card {{ $attributes }}>
    <flux:skeleton.group animate="shimmer">
        @isset($header)
            {{ $header }}
        @elseif ($heading !== false)
            <x-skeleton.heading :heading="$heading" :description="$description" :action="$action" />
        @endisset

        <div class="divide-y divide-zinc-800/10 dark:divide-white/20" aria-hidden="true">
            @foreach (range(0, $rows) as $row)
                <div class="grid items-center gap-4 py-3.5"
                    style="grid-template-columns: repeat({{ (int) $columns }}, minmax(0, 1fr));">
                    @foreach (range(1, $columns) as $column)
                        <flux:skeleton.line @class([
                            'w-1/2' => $row === 0,
                            'w-3/4' => $row > 0 && $column === 1,
                            'w-2/3' => $row > 0 && $column > 1,
                        ]) />
                    @endforeach
                </div>
            @endforeach
        </div>
    </flux:skeleton.group>
</flux:card>
