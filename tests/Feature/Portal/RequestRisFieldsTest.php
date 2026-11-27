<?php

use App\Livewire\Portal\RequestForm;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    fakeAreaHierarchy();
    RateLimiter::clear('public-request:127.0.0.1');

    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true]);
});

/**
 * Submit a portal request from the given administrative area.
 */
function submitFromArea(int $areaId, string $position = ''): void
{
    Livewire::test(RequestForm::class)
        ->set('form.requester_name', 'Juan Dela Cruz')
        ->set('form.supervisor_name', 'Maria Santos')
        ->set('form.requester_position', $position)
        ->set('form.administrative_area_id', $areaId)
        ->set('form.purpose', 'Monthly supplies')
        ->set('form.items.0.inventory_item_id', test()->paper->id)
        ->set('form.items.0.requested_qty', 5)
        ->call('review')
        ->assertHasNoErrors()
        ->call('submit')
        ->assertSet('step', 'done');
}

it('saves the requester position', function () {
    submitFromArea(11, '  Nurse II  ');

    expect(Requisitions::sole()->requester_position)->toBe('Nurse II');
});

it('stores no position when none is given', function () {
    submitFromArea(11);

    expect(Requisitions::sole()->requester_position)->toBeNull();
});

it('snapshots the picked area as the office and its parent as the division', function (int $areaId, int $officeId, string $office, int $divisionId, string $division) {
    submitFromArea($areaId);

    expect(Requisitions::sole())
        ->office_area_id->toBe($officeId)
        ->office->toBe($office)
        ->division_area_id->toBe($divisionId)
        ->division->toBe($division);
})->with([
    'a unit under a division' => [11, 11, 'Emergency Unit (EU)', 10, 'Nursing Service Division (NSD)'],
    'a section under an office' => [48, 48, 'Accounting Section (AS)', 6, 'Finance Office (FO)'],
    'a top-level area is its own division' => [1, 1, 'Office of the Medical Center Chief (OMCC)', 1, 'Office of the Medical Center Chief (OMCC)'],
]);
