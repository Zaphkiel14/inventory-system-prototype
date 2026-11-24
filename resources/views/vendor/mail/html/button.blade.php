@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    $bg = match ($color) {
        'success', 'green' => '#16a34a',
        'error', 'red' => '#dc2626',
        default => '#18181b',
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
<tr>
<td align="{{ $align }}">
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:40px;v-text-anchor:middle;width:240px;" arcsize="10%" stroke="f" fillcolor="{{ $bg }}"><w:anchorlock/><center style="color:#ffffff;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:bold;">{!! strip_tags($slot) !!}</center></v:roundrect>
<![endif]-->
<!--[if !mso]><!-->
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center" bgcolor="{{ $bg }}" style="background-color: {{ $bg }}; border-radius: 4px;">
<a href="{{ $url }}" class="button button-{{ $color }}" style="background-color: {{ $bg }}; border-radius: 4px;">{!! $slot !!}</a>
</td>
</tr>
</table>
<!--<![endif]-->
</td>
</tr>
</table>
