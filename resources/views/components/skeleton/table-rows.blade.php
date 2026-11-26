@blaze(fold: true)
{{--
Skeleton table body (header row + rows) without card chrome, for a table inside an existing card:
<x-skeleton.table-rows :rows="8" :columns="9" />
--}}
@props(['rows' => 5, 'columns' => 4])

<div {{ $attributes->class('divide-y divide-zinc-800/10 dark:divide-white/20') }} aria-hidden="true">
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
