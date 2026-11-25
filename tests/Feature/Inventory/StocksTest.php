<?php

use App\Livewire\Inventory\Stocks;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->janitorial = InventoryCategories::create(['name' => 'Test Janitorial', 'description' => 'Cleaning supplies.', 'is_active' => true]);
    $this->office = InventoryCategories::create(['name' => 'Test Office', 'description' => 'Paper and pens.', 'is_active' => true]);

    $this->bleach = InventoryItems::factory()->create(['name' => 'Bleach 1L', 'is_active' => true]);
    $this->bleach->inventoryCategory()->sync([$this->janitorial->id]);

    $this->paper = InventoryItems::factory()->create(['name' => 'Bond Paper', 'is_active' => true]);
    $this->paper->inventoryCategory()->sync([$this->office->id]);

    $this->bleachBatch = InventoryBatches::factory()->create(['inventory_item_id' => $this->bleach->id, 'batch_number' => 'BAT-BLEACH', 'expiration_date' => null]);
    $this->paperBatch = InventoryBatches::factory()->create(['inventory_item_id' => $this->paper->id, 'batch_number' => 'BAT-PAPER', 'expiration_date' => null]);
});

test('the category filter lists categories and limits batches to that category', function () {
    Livewire::test(Stocks::class)
        ->assertSee('All categories')
        ->assertSee('Test Janitorial')
        ->assertSee('BAT-BLEACH')
        ->assertSee('BAT-PAPER')
        ->set('category', $this->janitorial->id)
        ->assertSee('BAT-BLEACH')
        ->assertDontSee('BAT-PAPER')
        ->set('category', $this->office->id)
        ->assertSee('BAT-PAPER')
        ->assertDontSee('BAT-BLEACH');
});

test('the category filter is read from the url and cleared with the other filters', function () {
    Livewire::withQueryParams(['category' => $this->office->id])->test(Stocks::class)
        ->assertSet('category', $this->office->id)
        ->assertDontSee('BAT-BLEACH')
        ->call('clearFilters')
        ->assertSet('category', null)
        ->assertSee('BAT-BLEACH');
});

test('search matches category names', function () {
    Livewire::test(Stocks::class)
        ->set('search', 'Janitorial')
        ->assertSee('BAT-BLEACH')
        ->assertDontSee('BAT-PAPER');
});

test('each batch card shows its item categories', function () {
    Livewire::test(Stocks::class)
        ->set('category', $this->janitorial->id)
        // Card order: item name, its category badge, then the batch number.
        ->assertSeeInOrder(['Bleach 1L', 'Test Janitorial', 'BAT-BLEACH']);
});
