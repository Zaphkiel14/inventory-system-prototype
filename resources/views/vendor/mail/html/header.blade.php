@props(['url', 'logo' => null])
<tr>
<td class="header" align="center">
<a href="{{ $url }}">
@if ($logo)
<img src="{{ $logo }}" class="logo" width="75" height="75" alt="{{ config('app.name') }} logo" style="display: block; margin: 0 auto 10px; border: 0;">
@endif
{!! $slot !!}
</a>
</td>
</tr>
