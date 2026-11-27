@php
    $quantity = fn ($value): string => $value === null || $value === '' ? '' : (is_numeric($value) ? number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 2) : (string) $value);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Requisition and Issue Slip {{ $ris_number }}</title>
@include('pdf.partials.property-form-styles')
<style>
    /* RIS-only: Division/Office header rows, column groups, tick marks, NOTHING FOLLOWS */
    .head td { border: 1px solid #000; padding: 3px 4px; }
    .group { font-style: italic; font-size: 9px; }
    .tick { font-size: 10px; }
    .nothing { text-align: center; font-weight: bold; letter-spacing: 1px; padding: 5px; }
</style>
</head>
<body>
    @include('pdf.partials.property-form-head', [
        'appendix' => 'Appendix 63',
        'revision' => 'Rev 1 · 03 Feb 2016',
        'title' => 'REQUISITION AND ISSUE SLIP',
        'section' => null,
        'numberLabel' => null,
    ])

    <table class="meta">
        <tr>
            <td class="label" style="width: 12%;">Entity Name:</td>
            <td class="fill" style="width: 48%;">{{ $entity_name }}</td>
            <td class="label" style="width: 12%; padding-left: 16px;">Fund Cluster:</td>
            <td class="fill">{{ $fund_cluster }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr class="head">
                <td colspan="3" style="text-align: left;">
                    Division: <span class="fill">{{ $division }}</span>
                </td>
                <td colspan="5" style="text-align: left;">
                    Responsibility Center Code: <span class="fill">{{ $rc_code }}</span>
                </td>
            </tr>
            <tr class="head">
                <td colspan="3" style="text-align: left;">
                    Office: <span class="fill">{{ $office }}</span>
                </td>
                <td colspan="5" style="text-align: left;">
                    RIS No.: <span class="fill">{{ $ris_number }}</span>
                </td>
            </tr>
            <tr>
                <th colspan="4" class="group">Requisition</th>
                <th colspan="2" class="group">Stock Available?</th>
                <th colspan="2" class="group">Issue</th>
            </tr>
            <tr>
                <th style="width: 9%;">Stock No.</th>
                <th style="width: 7%;">Unit</th>
                <th style="width: 36%;">Description</th>
                <th style="width: 8%;">Quantity</th>
                <th style="width: 6%;">Yes</th>
                <th style="width: 6%;">No</th>
                <th style="width: 8%;">Quantity</th>
                <th style="width: 20%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                @php
                    $available = $line['stock_available'] ?? null;
                    $units = $line['units'] ?? [];
                @endphp
                <tr>
                    <td class="c">{{ $line['stock_no'] ?? '' }}</td>
                    <td class="c">{{ $line['unit'] ?? '' }}</td>
                    <td>
                        <div class="item">{{ $line['description'] ?? '' }}</div>
                        @foreach ($units as $unit)
                            @php
                                $parts = array_filter([
                                    filled($unit['brand'] ?? null) ? 'Brand: '.$unit['brand'] : null,
                                    filled($unit['model'] ?? null) ? 'Model: '.$unit['model'] : null,
                                    filled($unit['serial_number'] ?? null) ? 'Serial: '.$unit['serial_number'] : null,
                                ]);
                            @endphp
                            @if ($parts !== [])
                                <div class="unit-line">{{ implode('  /  ', $parts) }}</div>
                            @endif
                        @endforeach
                    </td>
                    <td class="c">{{ $quantity($line['quantity'] ?? null) }}</td>
                    <td class="c tick">{{ $available === 'Yes' ? '✓' : '' }}</td>
                    <td class="c tick">{{ $available === 'No' ? '✓' : '' }}</td>
                    <td class="c">{{ $quantity($line['issue_qty'] ?? null) }}</td>
                    <td>{{ $line['remarks'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="8" class="nothing">*** NOTHING FOLLOWS ***</td>
            </tr>
            <tr>
                <td colspan="8" style="height: 30px;">
                    Purpose: {{ $purpose }}
                </td>
            </tr>
        </tbody>
    </table>

    @include('pdf.partials.property-signatures', [
        'layout' => 'shared',
        'positionLabel' => 'Designation:',
        'columns' => [
            'Requested by:' => $requested_by ?? [],
            'Approved by:' => $approved_by ?? [],
            'Issued by:' => $issued_by ?? [],
            'Received by:' => $received_by ?? [],
        ],
    ])
</body>
</html>
