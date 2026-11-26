<?php

use App\Livewire\Inventory\ReceivingCreateForm;
use App\Livewire\Inventory\Stocks;
use App\Livewire\Monitoring\Reports;
use App\Livewire\Monitoring\TransactionHistory;
use App\Models\User;
use Livewire\Livewire;

it('renders a date and time picker bound to its model', function () {
    $this->withViewErrors([])
        ->blade('<x-datetime-picker wire:model="form.received_date" label="Received Date & Time" min="2026-01-01" minute-step="15" />')
        ->assertSee('Received Date &amp; Time', false)
        ->assertDontSee('&amp;amp;', false)
        ->assertSee('wire:model="form.received_date"', false)
        ->assertSee('x-data="datetimePicker(', false)
        ->assertSee('Manila', false)
        ->assertSee('Select date and time')
        ->assertSee('data-datetime-heading', false)
        ->assertSee('x-on:click="toggleMode()"', false)
        ->assertSee('x-on:click="pickMonth(option)"', false)
        ->assertSee('x-on:click="pickYear(option)"', false)
        ->assertSee('<option value="45">45</option>', false)
        ->assertDontSee('<option value="05">05</option>', false);
});

it('hides the time fields in date-only mode', function () {
    $this->withViewErrors([])
        ->blade('<x-datetime-picker wire:model="form.expiration_date" :time="false" clearable />')
        ->assertSee('Select date')
        ->assertDontSee('aria-label="Hour"', false)
        ->assertSee('Clear')
        ->assertSee('Today');
});

it('shows the validation error for its field', function () {
    $this->withViewErrors(['form.delivery_date' => 'The delivery date field is required.'])
        ->blade('<x-datetime-picker wire:model="form.delivery_date" label="Delivery" />')
        ->assertSee('The delivery date field is required.')
        ->assertSee('border-red-500');
});

it('renders the date picker as the shared picker without time fields', function () {
    $this->withViewErrors([])
        ->blade('<x-date-picker wire:model.live="from" size="sm" align="end" aria-label="From" class="sm:w-44" min="2026-01-01" clearable />')
        ->assertSee('wire:model.live="from"', false)
        ->assertSee('time', false)
        ->assertSee(':false,', false)
        ->assertSee('2026-01-01', false)
        ->assertSee('Select date')
        ->assertDontSee('Select date and time')
        ->assertDontSee('aria-label="Hour"', false)
        ->assertSee('h-8 rounded-md', false)
        ->assertSee('absolute end-0', false)
        ->assertSee('sm:w-44', false)
        ->assertSee('From', false)
        ->assertSee('Clear')
        ->assertSee('Today')
        ->assertSee('data-datetime-heading', false);
});

it('replaces every native date input in the app', function () {
    $views = collect(File::allFiles(resource_path('views/livewire')))
        ->filter(fn ($file) => str_contains(File::get($file->getPathname()), 'type="date"'))
        ->map(fn ($file) => $file->getRelativePathname());

    expect($views->all())->toBe([]);
});

it('renders date pickers on the custom-range filters', function () {
    $this->actingAs(User::factory()->create());

    Livewire::withoutLazyLoading()->test(Stocks::class)
        ->set('expiry', 'custom')
        ->assertSeeHtml('wire:model.live="expiryFrom"')
        ->assertSeeHtml('data-datetime-picker');

    Livewire::test(Reports::class)
        ->set('range', 'custom')
        ->assertSeeHtml('wire:model.live="from"')
        ->assertSeeHtml('data-datetime-picker');

    Livewire::test(TransactionHistory::class)
        ->set('range', 'custom')
        ->assertSeeHtml('wire:model.live="to"')
        ->assertSeeHtml('data-datetime-picker');
});

it('is used for both dates on the receiving form', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ReceivingCreateForm::class)
        ->assertSeeHtml('wire:model="form.received_date"')
        ->assertSeeHtml('wire:model="form.delivery_date"')
        ->assertDontSeeHtml('type="datetime-local"');
});
