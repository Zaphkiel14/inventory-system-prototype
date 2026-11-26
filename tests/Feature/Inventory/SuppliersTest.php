<?php

use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\SupplierForm;
use App\Livewire\Inventory\Suppliers as SuppliersPage;
use App\Models\StockReceipts;
use App\Models\Suppliers;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the suppliers page with delivery counts', function () {
    $supplier = Suppliers::factory()->create(['name' => 'Acme Medical']);
    StockReceipts::factory()->count(2)->create(['supplier_id' => $supplier->id]);

    // The page is deferred: the first response is the skeleton, and the
    // supplier rows arrive with the deferred load (asserted below).
    $this->get(route('inventory.suppliers'))
        ->assertOk()
        ->assertSee('Loading suppliers');

    $component = Livewire::withoutLazyLoading()->test(SuppliersPage::class)->assertSee('Acme Medical');

    expect($component->instance()->suppliers->first()->stock_receipts_count)->toBe(2);
});

it('searches and filters suppliers by status', function () {
    Suppliers::factory()->create(['name' => 'Alpha Supplies']);
    Suppliers::factory()->inactive()->create(['name' => 'Beta Traders']);

    Livewire::withoutLazyLoading()->test(SuppliersPage::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Supplies')
        ->assertDontSee('Beta Traders')
        ->set('search', '')
        ->set('status', 'inactive')
        ->assertSee('Beta Traders')
        ->assertDontSee('Alpha Supplies');
});

it('creates a supplier', function () {
    Livewire::test(SupplierForm::class)
        ->set('form.name', 'New Supplier Co.')
        ->set('form.contact_person', 'Maria Santos')
        ->set('form.email', 'orders@newsupplier.test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('supplier-saved');

    $supplier = Suppliers::where('name', 'New Supplier Co.')->first();

    expect($supplier)->not->toBeNull()
        ->and($supplier->contact_person)->toBe('Maria Santos')
        ->and($supplier->contact)->toBeNull()
        ->and($supplier->is_active)->toBeTrue();
});

it('validates supplier fields', function () {
    Suppliers::factory()->create(['name' => 'Taken']);

    Livewire::test(SupplierForm::class)
        ->call('save')
        ->assertHasErrors(['form.name' => 'required'])
        ->set('form.name', 'Taken')
        ->set('form.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique', 'form.email' => 'email']);
});

it('edits a supplier without tripping its own unique name', function () {
    $supplier = Suppliers::factory()->create(['name' => 'Old Name']);

    Livewire::test(SupplierForm::class)
        ->call('edit', $supplier->id)
        ->assertSet('form.name', 'Old Name')
        ->call('save')
        ->assertHasNoErrors()
        ->call('edit', $supplier->id)
        ->set('form.name', 'New Name')
        ->set('form.is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($supplier->fresh())
        ->name->toBe('New Name')
        ->is_active->toBeFalse();
});

it('toggles a supplier active state', function () {
    $supplier = Suppliers::factory()->create();

    Livewire::withoutLazyLoading()->test(SuppliersPage::class)->call('toggleActive', $supplier->id);

    expect($supplier->fresh()->is_active)->toBeFalse();
});

it('deletes a supplier with no deliveries', function () {
    $supplier = Suppliers::factory()->create();

    Livewire::withoutLazyLoading()->test(SuppliersPage::class)->call('delete', $supplier->id);

    expect(Suppliers::find($supplier->id))->toBeNull();
});

it('refuses to delete a supplier that has deliveries', function () {
    $supplier = Suppliers::factory()->create();
    StockReceipts::factory()->create(['supplier_id' => $supplier->id]);

    Livewire::withoutLazyLoading()->test(SuppliersPage::class)->call('delete', $supplier->id);

    expect(Suppliers::find($supplier->id))->not->toBeNull();
});

it('hides inactive suppliers from the receiving form', function () {
    Suppliers::factory()->create(['name' => 'Active Vendor']);
    Suppliers::factory()->inactive()->create(['name' => 'Retired Vendor']);

    $names = Livewire::test(ReceivingCreateForm::class)->instance()->suppliers->pluck('name');

    expect($names)->toContain('Active Vendor')->not->toContain('Retired Vendor');
});
