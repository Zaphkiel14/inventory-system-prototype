<?php

use App\Livewire\Inventory\ItemCreateForm;
use App\Livewire\Inventory\ItemEditForm;
use App\Models\InventoryItems;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

/**
 * The create-item modal with every required field filled in.
 */
function filledItemCreateForm(): Testable
{
    return Livewire::test(ItemCreateForm::class)
        ->set('form.item_code', 'EQP-0001')
        ->set('form.name', 'Electric Fan 16"')
        ->set('form.unit', 'unit')
        ->set('form.minimum_stock', 1)
        ->set('form.reorder_level', 2);
}

it('creates a property item with its default useful life', function () {
    filledItemCreateForm()
        ->set('form.is_property', true)
        ->set('form.default_useful_life_years', 5)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inventory-item-created');

    $this->assertDatabaseHas('inventory_items', [
        'item_code' => 'EQP-0001',
        'is_property' => true,
        'default_useful_life_years' => 5,
    ]);
});

it('stores no useful life for an item that is not property', function () {
    filledItemCreateForm()
        ->set('form.is_property', false)
        ->set('form.default_useful_life_years', 5)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('inventory_items', [
        'item_code' => 'EQP-0001',
        'is_property' => false,
        'default_useful_life_years' => null,
    ]);
});

it('rejects a useful life outside 1 to 100 years and creates no item', function (int $years, string $message) {
    filledItemCreateForm()
        ->set('form.is_property', true)
        ->set('form.default_useful_life_years', $years)
        ->call('save')
        ->assertHasErrors(['form.default_useful_life_years' => $message])
        ->assertNotDispatched('inventory-item-created');

    $this->assertDatabaseMissing('inventory_items', ['item_code' => 'EQP-0001']);
})->with([
    'zero years' => [0, 'The default useful life years field must be at least 1.'],
    'over 100 years' => [101, 'The default useful life years field must not be greater than 100.'],
]);

it('rejects an item code or name already used by another item', function (array $existing, string $field, string $message) {
    InventoryItems::factory()->create($existing);

    filledItemCreateForm()
        ->call('save')
        ->assertHasErrors(["form.{$field}" => $message])
        ->assertNotDispatched('inventory-item-created');

    $this->assertDatabaseCount('inventory_items', 1);
})->with([
    'duplicate item code' => [['item_code' => 'EQP-0001'], 'item_code', 'The item code has already been taken.'],
    'duplicate name' => [['name' => 'Electric Fan 16"'], 'name', 'The name has already been taken.'],
]);

it('loads the property fields into the edit form and clears the useful life when the flag is turned off', function () {
    $item = InventoryItems::factory()->property(7)->create();

    Livewire::test(ItemEditForm::class)
        ->call('edit', $item->id)
        ->assertSet('form.is_property', true)
        ->assertSet('form.default_useful_life_years', 7)
        ->set('form.is_property', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inventory-item-updated');

    expect($item->fresh())
        ->is_property->toBeFalse()
        ->default_useful_life_years->toBeNull();
});
