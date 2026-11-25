<?php

use App\Livewire\Inventory\Items;
use App\Models\InventoryBatches;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $this->item = InventoryItems::factory()->create([
        'item_code' => 'ITM-0042',
        'name' => 'Bond Paper A4',
        'unit' => 'ream',
        'requires_expiry' => false,
        'minimum_stock' => 5,
        'reorder_level' => 12,
        'is_active' => true,
    ]);

    $office = InventoryCategories::create(['name' => 'Test Office', 'description' => 'Paper, pens and folders.', 'is_active' => true]);
    $printing = InventoryCategories::create(['name' => 'Test Printing', 'description' => 'Toner and printing paper.', 'is_active' => false]);
    $this->item->inventoryCategory()->sync([$office->id, $printing->id]);

    InventoryBatches::factory()->create(['inventory_item_id' => $this->item->id, 'received_qty' => 30, 'remaining_qty' => 30, 'expiration_date' => null]);
    InventoryBatches::factory()->create(['inventory_item_id' => $this->item->id, 'received_qty' => 10, 'remaining_qty' => 0, 'expiration_date' => null]);
});

test('the item menu offers a view action', function () {
    Livewire::test(Items::class)
        ->assertSee('View')
        ->assertSeeHtml('wire:click="view('.$this->item->id.')"');
});

test('viewing an item opens a modal with all its details and categories', function () {
    Livewire::test(Items::class)
        ->call('view', $this->item->id)
        ->assertSet('viewingId', $this->item->id)
        ->assertDispatched('modal-show', name: 'view-item')
        ->assertSee('ITM-0042')
        ->assertSee('Bond Paper A4')
        ->assertSee('FIFO')
        ->assertSee('Minimum Stock')
        ->assertSee('Reorder Level')
        ->assertSee('Test Office')
        ->assertSee('Paper, pens and folders.')
        ->assertSee('Test Printing')
        ->assertSee('Categories')
        ->assertSee('2 batches received in total')
        ->tap(function ($page) {
            $viewed = $page->instance()->viewing;

            expect((int) $viewed->available_qty)->toBe(30)
                ->and($viewed->usable_batches_count)->toBe(1)
                ->and($viewed->inventory_batches_count)->toBe(2)
                ->and($viewed->inventoryCategory->pluck('name')->all())->toBe(['Test Office', 'Test Printing']);
        });
});

test('edit from the details modal hands the item to the edit modal', function () {
    Livewire::test(Items::class)
        ->call('view', $this->item->id)
        ->call('editViewing')
        ->assertDispatched('modal-close', name: 'view-item')
        ->assertDispatched('edit-inventory-item', itemId: $this->item->id);
});
