@php($format = 'M d, Y h:i A')
<x-mail::message :logo="\App\Support\MailLogo::src($message ?? null)">
<x-slot:preheader>Your request {{ $requisition->request_number }} is now {{ $status->label() }}.</x-slot:preheader>
# Hello {{ $requisition->requester_name }},

Your request **{{ $requisition->request_number }}** is now: **{{ $status->label() }}**.

Tracking code: {{ $requisition->tracking_code }}  
Submitted: {{ $requisition->submitted_at->format($format) }}
@if ($decidedAt)  
Decided: {{ $decidedAt->format($format) }}
@endif
@if ($releasedAt)  
Last released: {{ $releasedAt->format($format) }}
@endif

<x-mail::table>
| Item | Requested | Approved | Released |
| :--- | ---: | ---: | ---: |
@foreach ($requisition->items as $item)
| {{ $item->inventoryItem?->name ?? 'Item' }} | {{ $item->requested_qty }} | {{ $item->approved_qty ?? '-' }} | {{ $item->released_qty }} |
@endforeach
</x-mail::table>

<x-mail::button :url="$trackUrl">
Track your request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
