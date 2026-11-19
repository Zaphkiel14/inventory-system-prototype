@php
// Sample data so every chart type can be previewed side by side.
$days = collect(range(29, 0))->map(function ($ago) {
    $i = 30 - $ago;

    return [
        'date' => now()->subDays($ago)->toDateString(),
        'received' => (int) round(120 + 40 * sin($i / 4) + $i * 3),
        'issued' => (int) round(90 + 30 * cos($i / 5) + $i * 2),
    ];
});

$months = collect(['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'])->map(fn ($m, $i) => [
    'month' => $m,
    'received' => [420, 380, 510, 460, 590, 640][$i],
    'issued' => [310, 350, 400, 420, 470, 520][$i],
    'adjusted' => [40, 25, 60, 35, 30, 45][$i],
]);

$locations = collect([
    ['location' => 'Main Storage Room', 'stock' => 1840],
    ['location' => 'Shelf 1 - Ground Floor', 'stock' => 1260],
    ['location' => 'Cabinet 1 - Second Floor', 'stock' => 940],
    ['location' => 'Storage Rack 1 - Ground Floor', 'stock' => 720],
    ['location' => 'Shelf 2 - Third Floor', 'stock' => 410],
]);

$categories = collect([
    ['label' => 'Office Supplies', 'value' => 46],
    ['label' => 'Cleaning', 'value' => 21],
    ['label' => 'Medical', 'value' => 17],
    ['label' => 'IT Equipment', 'value' => 11],
    ['label' => 'Furniture', 'value' => 5],
]);

$scatter = collect(range(1, 24))->map(fn ($i) => ['n' => $i, 'value' => (int) round(30 + 25 * sin($i * 1.7) + $i * 2)]);
$spark = $days->pluck('received');
@endphp

<x-layouts::app :title="__('Charts')">
    <div class="flex flex-col gap-6">
        <div>
            <flux:heading size="xl" level="1">Charts</flux:heading>
            <flux:text class="mt-1">Every chart type built with &lt;x-chart&gt;. Hover a chart to see its tooltip.</flux:text>
        </div>

        {{-- ================= Line charts ================= --}}
        <flux:heading size="lg">Line charts</flux:heading>
        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading>Line</flux:heading>
                <flux:text class="mb-4">Received vs issued, last 30 days</flux:text>
                <x-chart :value="$days" class="aspect-[2/1]">
                    <x-chart.line field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.line field="issued" label="Issued" class="text-sky-500 dark:text-sky-400" dashed />
                    <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
                    <x-chart.axis axis="y" />
                    <x-chart.cursor />
                    <x-chart.tooltip :format="['dateStyle' => 'medium']" />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Area</flux:heading>
                <flux:text class="mb-4">Line with a gradient fill</flux:text>
                <x-chart :value="$days" class="aspect-[2/1]">
                    <x-chart.area field="received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.line field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.axis axis="x" field="date" :format="['month' => 'short', 'day' => 'numeric']" />
                    <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                    <x-chart.cursor />
                    <x-chart.tooltip :format="['dateStyle' => 'medium']" />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Stacked area</flux:heading>
                <flux:text class="mb-4">Areas stacked on top of each other</flux:text>
                <x-chart :value="$months" stacked class="aspect-[2/1]">
                    <x-chart.area field="issued" label="Issued" class="text-sky-500 dark:text-sky-400" :opacity="0.5" />
                    <x-chart.area field="adjusted" label="Adjusted" class="text-amber-500 dark:text-amber-400" :opacity="0.5" />
                    <x-chart.axis axis="x" field="month" />
                    <x-chart.axis axis="y" />
                    <x-chart.cursor />
                    <x-chart.tooltip />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Points</flux:heading>
                <flux:text class="mb-4">Dots only, or dots on a line</flux:text>
                <div class="grid grid-cols-2 gap-4">
                    <x-chart :value="$scatter" class="aspect-square">
                        <x-chart.point class="text-violet-500 dark:text-violet-400" label="Value" />
                        <x-chart.axis axis="x" field="n" :grid="true" />
                        <x-chart.axis axis="y" />
                        <x-chart.tooltip />
                    </x-chart>
                    <x-chart :value="$months" class="aspect-square">
                        <x-chart.line field="received" label="Received" curve="linear" class="text-rose-500 dark:text-rose-400" />
                        <x-chart.point field="received" class="text-rose-500 dark:text-rose-400" />
                        <x-chart.axis axis="x" field="month" />
                        <x-chart.axis axis="y" />
                        <x-chart.cursor />
                        <x-chart.tooltip />
                    </x-chart>
                </div>
            </flux:card>
        </div>

        <flux:card>
            <flux:heading>Sparklines</flux:heading>
            <flux:text class="mb-4">No axes or tooltip, just the trend. Pass a list of plain numbers.</flux:text>
            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['Received', $spark, 'text-emerald-500 dark:text-emerald-400'],
                    ['Issued', $days->pluck('issued'), 'text-sky-500 dark:text-sky-400'],
                    ['Adjusted', $months->pluck('adjusted'), 'text-amber-500 dark:text-amber-400'],
                ] as [$title, $series, $color])
                <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                    <div>
                        <flux:text>{{ $title }}</flux:text>
                        <flux:heading size="lg">{{ number_format($series->last()) }}</flux:heading>
                    </div>
                    <x-chart :value="$series" class="h-10 w-28">
                        <x-chart.area class="{{ $color }}" />
                        <x-chart.line class="{{ $color }}" :width="1.5" />
                    </x-chart>
                </div>
                @endforeach
            </div>
        </flux:card>

        {{-- ================= Bar charts ================= --}}
        <flux:heading size="lg">Bar charts</flux:heading>
        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading>Bars</flux:heading>
                <flux:text class="mb-4">Units received per month</flux:text>
                <x-chart :value="$months" class="aspect-[2/1]">
                    <x-chart.bar field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.axis axis="x" field="month" />
                    <x-chart.axis axis="y" />
                    <x-chart.cursor />
                    <x-chart.tooltip />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Grouped</flux:heading>
                <flux:text class="mb-4">Several series side by side</flux:text>
                <x-chart :value="$months" class="aspect-[2/1]">
                    <x-chart.bar field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.bar field="issued" label="Issued" class="text-sky-500 dark:text-sky-400" />
                    <x-chart.bar field="adjusted" label="Adjusted" class="text-amber-500 dark:text-amber-400" />
                    <x-chart.axis axis="x" field="month" />
                    <x-chart.axis axis="y" />
                    <x-chart.cursor />
                    <x-chart.tooltip />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Stacked</flux:heading>
                <flux:text class="mb-4">Series stacked into one column</flux:text>
                <x-chart :value="$months" stacked class="aspect-[2/1]">
                    <x-chart.bar field="received" label="Received" class="text-emerald-500 dark:text-emerald-400" />
                    <x-chart.bar field="issued" label="Issued" class="text-sky-500 dark:text-sky-400" />
                    <x-chart.bar field="adjusted" label="Adjusted" class="text-amber-500 dark:text-amber-400" />
                    <x-chart.axis axis="x" field="month" />
                    <x-chart.axis axis="y" :format="['notation' => 'compact']" />
                    <x-chart.cursor />
                    <x-chart.tooltip />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Horizontal</flux:heading>
                <flux:text class="mb-4">Stock on hand by storage location</flux:text>
                <x-chart :value="$locations" horizontal class="aspect-[2/1]">
                    <x-chart.bar field="stock" label="On hand" class="text-violet-500 dark:text-violet-400" />
                    <x-chart.axis axis="y" field="location" />
                    <x-chart.axis axis="x" />
                    <x-chart.cursor />
                    <x-chart.tooltip />
                </x-chart>
            </flux:card>
        </div>

        {{-- ================= Pie charts ================= --}}
        <flux:heading size="lg">Pie charts</flux:heading>
        <div class="grid gap-4 md:grid-cols-3">
            <flux:card>
                <flux:heading>Pie</flux:heading>
                <flux:text class="mb-4">Items by category</flux:text>
                <x-chart :value="$categories" class="h-64">
                    <x-chart.pie />
                    <x-chart.tooltip />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Donut</flux:heading>
                <flux:text class="mb-4">With a total in the middle</flux:text>
                <x-chart :value="$categories" class="h-64">
                    <x-chart.donut>
                        <flux:heading size="xl">{{ $categories->sum('value') }}</flux:heading>
                        <flux:text size="sm">items</flux:text>
                    </x-chart.donut>
                    <x-chart.tooltip />
                    <x-chart.legend />
                </x-chart>
            </flux:card>

            <flux:card>
                <flux:heading>Ring</flux:heading>
                <flux:text class="mb-4">Thin ring, or progress against a target</flux:text>
                <div class="grid grid-cols-2 items-center gap-4">
                    <x-chart :value="$categories" class="aspect-square">
                        <x-chart.ring :colors="[
                            'text-emerald-500 dark:text-emerald-400',
                            'text-sky-500 dark:text-sky-400',
                            'text-amber-500 dark:text-amber-400',
                            'text-violet-500 dark:text-violet-400',
                            'text-rose-500 dark:text-rose-400',
                        ]" />
                        <x-chart.tooltip />
                    </x-chart>
                    <x-chart :value="[['label' => 'Fulfilled', 'value' => 72]]" class="aspect-square">
                        <x-chart.ring :max="100" :colors="['text-emerald-500 dark:text-emerald-400']">
                            <flux:heading size="lg">72%</flux:heading>
                            <flux:text size="sm">fulfilled</flux:text>
                        </x-chart.ring>
                        <x-chart.tooltip />
                    </x-chart>
                </div>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
