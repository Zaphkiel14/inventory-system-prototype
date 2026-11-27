@php
    $peso = fn ($value): string => $value === null || $value === '' ? '' : '₱'.number_format((float) $value, 2);
    $quantity = fn ($value): string => $value === null || $value === '' ? '' : (is_numeric($value) ? number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 2) : (string) $value);
    $usefulLife = function ($years): string {
        if ($years === null || $years === '') {
            return '';
        }

        return $years.' '.((int) $years === 1 ? 'year' : 'years');
    };
    $grandTotal = collect($lines)->sum(fn ($line) => (float) ($line['total_cost'] ?? 0));
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Inventory Custodian Slip {{ $document_number }}</title>
@include('pdf.partials.property-form-styles')
</head>
<body>
    @include('pdf.partials.property-form-head', [
        'appendix' => 'Appendix 59',
        'revision' => 'Rev 0 · 17 Sept 2018',
        'title' => 'INVENTORY CUSTODIAN SLIP',
        'numberLabel' => 'ICS No.:',
    ])

    <table class="grid">
        <thead>
            <tr>
                <th rowspan="2" style="width: 8%;">Quantity</th>
                <th rowspan="2" style="width: 7%;">Unit</th>
                <th colspan="2">Amount</th>
                <th rowspan="2" style="width: 31%;">Description</th>
                <th rowspan="2" style="width: 20%;">Inventory Item No.</th>
                <th rowspan="2" style="width: 10%;">Estimated Useful Life</th>
            </tr>
            <tr>
                <th style="width: 12%;">Unit Cost</th>
                <th style="width: 12%;">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td class="c">{{ $quantity($line['quantity'] ?? null) }}</td>
                    <td class="c">{{ $line['unit'] ?? '' }}</td>
                    <td class="r">{{ $peso($line['unit_cost'] ?? null) }}</td>
                    <td class="r">{{ $peso($line['total_cost'] ?? null) }}</td>
                    <td>@include('pdf.partials.property-line-description', ['line' => $line])</td>
                    <td class="c" style="font-size: 8px;">{{ $line['property_number'] ?? '' }}</td>
                    <td class="c">{{ $usefulLife($line['estimated_useful_life_years'] ?? null) }}</td>
                </tr>
            @endforeach

            @if ($show_total ?? false)
                <tr class="total">
                    <td colspan="3" class="r">Total</td>
                    <td class="r">{{ $peso($grandTotal) }}</td>
                    <td colspan="3"></td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('pdf.partials.property-signatures', [
        'columns' => [
            'Received from:' => $from ?? [],
            'Received by:' => $to ?? [],
        ],
    ])
</body>
</html>
