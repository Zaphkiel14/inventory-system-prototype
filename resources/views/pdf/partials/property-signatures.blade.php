{{--
Signature block for the RIS, ICS and PAR printouts.

Expects $columns: [heading => [name, position|designation, date]], left column first.
Optional $layout:
  'paired' (default, ICS/PAR)  each signatory has its own label and value cells
  'shared' (RIS)               one label column on the left, one value column per signatory
Optional $positionLabel: the third row's label (default "Position/Office:"; the RIS uses "Designation:").
--}}
@php
    $layout ??= 'paired';
    $positionLabel ??= 'Position/Office:';
    $signatureDate = fn ($value): string => $value instanceof \DateTimeInterface ? $value->format('m/d/Y') : (string) ($value ?? '');
    $position = fn (array $person): string => (string) ($person['position'] ?? $person['designation'] ?? '');
@endphp
<div class="keep-together">
    @if ($layout === 'shared')
        <table class="sign sign-shared">
            <tr>
                <td class="row-head"></td>
                @foreach ($columns as $heading => $person)
                    <td class="col-head" style="width: {{ round(86 / max(1, count($columns)), 2) }}%;">{{ $heading }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-head sig">Signature:</td>
                @foreach ($columns as $person)
                    <td class="sig"></td>
                @endforeach
            </tr>
            <tr>
                <td class="row-head">Printed Name:</td>
                @foreach ($columns as $person)
                    <td class="c" style="font-weight: bold;">{{ $person['name'] ?? '' }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-head">{{ $positionLabel }}</td>
                @foreach ($columns as $person)
                    <td class="c">{{ $position($person) }}</td>
                @endforeach
            </tr>
            <tr>
                <td class="row-head">Date:</td>
                @foreach ($columns as $person)
                    <td class="c">{{ $signatureDate($person['date'] ?? null) }}</td>
                @endforeach
            </tr>
        </table>
    @else
        <table class="sign">
            <tr>
                @foreach ($columns as $heading => $person)
                    <td colspan="2" class="col-head">{{ $heading }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($columns as $person)
                    <td class="row-label sig">Signature:</td>
                    <td class="value sig"></td>
                @endforeach
            </tr>
            <tr>
                @foreach ($columns as $person)
                    <td class="row-label">Printed Name:</td>
                    <td class="value" style="font-weight: bold;">{{ $person['name'] ?? '' }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($columns as $person)
                    <td class="row-label">{{ $positionLabel }}</td>
                    <td class="value">{{ $position($person) }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach ($columns as $person)
                    <td class="row-label">Date:</td>
                    <td class="value">{{ $signatureDate($person['date'] ?? null) }}</td>
                @endforeach
            </tr>
        </table>
    @endif
</div>
