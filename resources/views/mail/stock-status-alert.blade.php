@php($cell = fn ($value) => str_replace('|', '\|', (string) $value))
<x-mail::message :logo="\App\Support\MailLogo::src($message ?? null)">
<x-slot:preheader>{{ count($items) === 1 ? $items[0]['name'].' is now '.$items[0]['status_label'].'.' : count($items).' items have dropped to a lower stock status.' }}</x-slot:preheader>
# Stock alert

@if (count($items) === 1)
**{{ $items[0]['name'] }}** has dropped to **{{ $items[0]['status_label'] }}**. Plan a restock before it runs out.
@else
**{{ count($items) }} items** have dropped to a lower stock status. Plan a restock before they run out.
@endif

<x-mail::table>
| Code | Item | Status | Available | Reorder level | Minimum |
| :--- | :--- | :--- | ---: | ---: | ---: |
@foreach ($items as $item)
| {{ $cell($item['item_code']) }} | {{ $cell($item['name']) }} | {{ $item['previous_label'] }} → **{{ $item['status_label'] }}** | {{ $item['available_qty'] }}{{ $item['unit'] ? ' '.$cell($item['unit']) : '' }} | {{ $item['reorder_level'] }} | {{ $item['minimum_stock'] }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$monitoringUrl">
Open Stock Monitoring
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
You get these alerts because stock alerts are on for your account. You can turn them off in Settings › Profile.
</x-slot:subcopy>
</x-mail::message>
