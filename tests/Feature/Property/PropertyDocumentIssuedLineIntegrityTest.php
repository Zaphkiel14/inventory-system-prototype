<?php

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Livewire\PropertyManagement\PropertyDocumentEditor;
use App\Models\IssuedPropertyUnits;
use App\Models\PropertyDocumentLines;
use App\Models\User;
use App\Services\Property\PropertyDocumentDrafter;
use Livewire\Livewire;

/**
 * Lines that came from an issuance carry the issued unit's quantity, unit and
 * cost. The editor shows them read-only, but the server must not trust a
 * posted value either: cost decides the class, and so which form a unit is on.
 */
beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->unit = IssuedPropertyUnits::factory()->ofClass(PropertyClass::Splv)->create(['unit_cost' => '1196.11']);
    $this->otherUnit = IssuedPropertyUnits::factory()->ofClass(PropertyClass::Splv)->create();

    $issuance = $this->unit->stockIssuanceBatch->stockIssuanceItem->stockIssuance;
    $this->document = app(PropertyDocumentDrafter::class)->forIssuance($issuance)->sole();
    $this->line = $this->document->lines->sole();
});

function tamperedLine(PropertyDocumentLines $line, int $otherUnitId): array
{
    return [
        'id' => $line->id,
        'issued_property_unit_id' => $otherUnitId,
        'inventory_item_id' => null,
        'quantity' => 5,
        'unit' => 'box',
        'unit_cost' => '78000.00',
        'item_name' => $line->item_name,
        'item_type' => '',
        'brand' => 'Edited brand',
        'model' => '',
        'serial_number' => '',
        'property_number' => 'LV2026-0001',
        'estimated_useful_life_years' => 5,
        'date_acquired' => '2026-10-01',
    ];
}

it('ignores tampered quantity, cost, unit and unit link on save', function () {
    Livewire::test(PropertyDocumentEditor::class)
        ->dispatch('edit-property-document', id: $this->document->id)
        ->set('form.lines', [tamperedLine($this->line, $this->otherUnit->id)])
        ->call('save')
        ->assertHasNoErrors();

    $line = $this->line->fresh();

    expect($line->issued_property_unit_id)->toBe($this->unit->id)
        ->and($line->quantity)->toBe(1)
        ->and($line->unit_cost)->toBe('1196.11')
        ->and($line->unit)->toBe($this->unit->inventoryItem->unit)
        ->and($line->brand)->toBe('Edited brand')
        ->and($this->otherUnit->fresh()->documentLine)->toBeNull();
});

it('ignores tampered values on finalize, so a SPLV unit cannot be pushed onto a PAR-level cost', function () {
    Livewire::test(PropertyDocumentEditor::class)
        ->dispatch('edit-property-document', id: $this->document->id)
        ->set('form.document_number', 'SPLV-2026-10-062')
        ->set('form.fund_cluster', '01')
        ->set('form.from_name', 'Supply Officer')->set('form.from_position', 'Supply Officer III')
        ->set('form.to_name', 'Juan Dela Cruz')->set('form.to_position', 'Nurse II')
        ->set('form.lines', [tamperedLine($this->line, $this->otherUnit->id)])
        ->call('finalize')
        ->assertHasNoErrors();

    $line = $this->line->fresh();

    expect($this->document->fresh()->status)->toBe(PropertyDocumentStatus::Active)
        ->and($line->unit_cost)->toBe('1196.11')
        ->and($line->quantity)->toBe(1)
        ->and($line->issued_property_unit_id)->toBe($this->unit->id);
});

it('saves a new line that posts an issued unit id as hand-encoded', function () {
    $newLine = [...tamperedLine($this->line, $this->otherUnit->id), 'id' => null, 'property_number' => 'LV2026-0002', 'unit_cost' => '500.00', 'quantity' => 2];

    Livewire::test(PropertyDocumentEditor::class)
        ->dispatch('edit-property-document', id: $this->document->id)
        ->set('form.lines', [...Livewire::test(PropertyDocumentEditor::class)
            ->dispatch('edit-property-document', id: $this->document->id)->get('form.lines'), $newLine])
        ->call('save')
        ->assertHasNoErrors();

    $added = PropertyDocumentLines::query()->where('property_document_id', $this->document->id)->whereKeyNot($this->line->id)->sole();

    expect($added->issued_property_unit_id)->toBeNull()
        ->and($added->unit_cost)->toBe('500.00')
        ->and($added->quantity)->toBe(2)
        ->and($this->otherUnit->fresh()->documentLine)->toBeNull();
});
