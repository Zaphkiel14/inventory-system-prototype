<?php

use App\Livewire\Configurations\StorageLocationForm;
use App\Livewire\Configurations\StorageLocations as StorageLocationsPage;
use App\Models\InventoryBatches;
use App\Models\StorageLocations;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the storage locations page with batch counts', function () {
    $location = StorageLocations::factory()->create(['name' => 'Shelf A', 'type' => 'Shelf']);
    InventoryBatches::factory()->count(2)->create(['storage_location_id' => $location->id]);

    $this->get(route('configurations.storage-locations'))
        ->assertOk()
        ->assertSee('Shelf A');

    $component = Livewire::test(StorageLocationsPage::class)->assertSee('Shelf A');

    expect($component->instance()->locations->first()->inventory_batches_count)->toBe(2);
});

it('creates a storage location', function () {
    Livewire::test(StorageLocationForm::class)
        ->set('form.name', 'Cabinet Z')
        ->set('form.type', 'Cabinet')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('storage-location-saved');

    expect(StorageLocations::where('name', 'Cabinet Z')->exists())->toBeTrue();
});

it('validates required fields', function () {
    Livewire::test(StorageLocationForm::class)
        ->call('save')
        ->assertHasErrors(['form.name', 'form.type']);
});

it('edits a storage location', function () {
    $location = StorageLocations::factory()->create(['name' => 'Old', 'type' => 'Shelf']);

    Livewire::test(StorageLocationForm::class)
        ->call('edit', $location->id)
        ->assertSet('form.name', 'Old')
        ->set('form.name', 'New')
        ->call('save')
        ->assertHasNoErrors();

    expect($location->fresh()->name)->toBe('New');
});

it('deletes an empty storage location', function () {
    $location = StorageLocations::factory()->create();

    Livewire::test(StorageLocationsPage::class)->call('delete', $location->id);

    expect(StorageLocations::find($location->id))->toBeNull();
});

it('refuses to delete a storage location that has batches', function () {
    $location = StorageLocations::factory()->create();
    InventoryBatches::factory()->create(['storage_location_id' => $location->id]);

    Livewire::test(StorageLocationsPage::class)->call('delete', $location->id);

    expect(StorageLocations::find($location->id))->not->toBeNull();
});
