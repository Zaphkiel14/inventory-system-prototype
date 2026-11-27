{{--
Shared top of the RIS, ICS and PAR printouts: appendix label, revision,
letterhead with the optional logo (left cell; the empty right cell keeps the
text centred), the form title, then Fund Cluster and the document number.

Expects: $appendix, $revision, $title, plus the controller's $logo,
$entity_name and $entity_address.
Optional:
  $section     line under the address (default "Property and Supply Section"; null hides it)
  $numberLabel with $fund_cluster and $document_number, prints the Fund Cluster / number row;
               leave $numberLabel null to skip the row (the RIS prints its own)
--}}
@php
    $section = array_key_exists('section', get_defined_vars()) ? $section : 'Property and Supply Section';
    $numberLabel ??= null;
@endphp
<div class="appendix">{{ $appendix }}</div>
<div class="revision">{{ $revision }}</div>

<table class="letterhead-table">
    <tr>
        <td class="logo-cell">
            @if (filled($logo ?? null))
                <img src="{{ $logo }}" alt="" class="logo">
            @endif
        </td>
        <td class="letterhead">
            <div class="entity">{{ $entity_name }}</div>
            @if (filled($entity_address ?? null))
                <div class="address">{{ $entity_address }}</div>
            @endif
            @if (filled($section))
                <div class="section">{{ $section }}</div>
            @endif
        </td>
        <td class="logo-cell"></td>
    </tr>
</table>

<div class="title">{{ $title }}</div>

@if ($numberLabel !== null)
    <table class="meta">
        <tr>
            <td class="label" style="width: 11%;">Fund Cluster:</td>
            <td class="fill" style="width: 44%;">{{ $fund_cluster }}</td>
            <td class="label" style="width: 15%; padding-left: 16px;">{{ $numberLabel }}</td>
            <td class="fill" style="font-weight: bold;">{{ $document_number }}</td>
        </tr>
    </table>
@endif
