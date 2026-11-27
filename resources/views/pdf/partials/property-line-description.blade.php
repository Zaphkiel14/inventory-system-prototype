{{-- Description cell for an ICS/PAR line: bold item name, its type, then brand, model and serial on their own small lines. Expects $line. --}}
<div class="item">{{ $line['item_name'] ?? '' }}</div>
@if (filled($line['item_type'] ?? null))
    <div class="item-type">{{ $line['item_type'] }}</div>
@endif
@if (filled($line['brand'] ?? null))
    <div class="unit-line">Brand: {{ $line['brand'] }}</div>
@endif
@if (filled($line['model'] ?? null))
    <div class="unit-line">Model: {{ $line['model'] }}</div>
@endif
@if (filled($line['serial_number'] ?? null))
    <div class="unit-line">Serial No.: {{ $line['serial_number'] }}</div>
@endif
