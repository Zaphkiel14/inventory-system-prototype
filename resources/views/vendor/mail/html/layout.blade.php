<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<title>{{ config('app.name') }}</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<![endif]-->
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}

.content-cell {
padding: 20px !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<div>
@if (isset($preheader) && trim((string) $preheader) !== '')
{{-- Hidden with display/mso-hide only: matching the text colour to the background or shrinking it trips SpamAssassin (HTML_FONT_LOW_CONTRAST, FONT_INVIS_*). --}}
<div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">{{ $preheader }}&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;&#8203;&nbsp;</div>
@endif

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" bgcolor="#fafafa">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" bgcolor="#fafafa">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" border="0" role="presentation" bgcolor="#ffffff">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</div>
</html>
