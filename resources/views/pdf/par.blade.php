@php
    $peso = fn ($value): string => $value === null || $value === '' ? '' : '₱'.number_format((float) $value, 2);
    $quantity = fn ($value): string => $value === null || $value === '' ? '' : (is_numeric($value) ? number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 2) : (string) $value);
    $acquired = fn ($value): string => $value instanceof \DateTimeInterface ? $value->format('m/d/Y') : (string) ($value ?? '');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Property Acknowledgment Receipt {{ $document_number }}</title>
@include('pdf.partials.property-form-styles')
</head>
<body>
    @include('pdf.partials.property-form-head', [
        'appendix' => 'Appendix 71',
        'revision' => 'Rev 0 · 17 Sept 2018',
        'title' => 'PROPERTY ACKNOWLEDGMENT RECEIPT',
        'numberLabel' => 'PAR No.:',
    ])

    <table class="grid">
        <thead>
            <tr>
                <th style="width: 8%;">Quantity</th>
                <th style="width: 7%;">Unit</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 19%;">Property Number</th>
                <th style="width: 12%;">Date Acquired</th>
                <th style="width: 14%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td class="c">{{ $quantity($line['quantity'] ?? null) }}</td>
                    <td class="c">{{ $line['unit'] ?? '' }}</td>
                    <td>@include('pdf.partials.property-line-description', ['line' => $line])</td>
                    <td class="c">{{ $line['property_number'] ?? '' }}</td>
                    <td class="c">{{ $acquired($line['date_acquired'] ?? null) }}</td>
                    <td class="r">{{ $peso($line['total_cost'] ?? $line['unit_cost'] ?? null) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('pdf.partials.property-signatures', [
        'columns' => [
            'Received by:' => $to ?? [],
            'Issued by:' => $from ?? [],
        ],
    ])
</body>
</html>
