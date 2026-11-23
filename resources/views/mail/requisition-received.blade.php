<x-mail::message>
# Hello {{ $requisition->requester_name }},

We received your request **{{ $requisition->request_number }}**.

Your tracking code is: **{{ $requisition->tracking_code }}**

We will email you whenever the status of this request changes.

<x-mail::button :url="$trackUrl">
Track your request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
