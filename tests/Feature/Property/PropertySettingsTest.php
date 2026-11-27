<?php

use App\Enums\IcsTagging;
use App\Enums\PaperSize;
use App\Livewire\Configurations\PropertySettings as PropertySettingsPage;
use App\Models\User;
use App\Support\PropertySettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('returns the defaults when no setting has been saved', function () {
    expect(PropertySettings::entityName())->toBe('Governor Benjamin T. Romualdez General Hospital and Schistosomiasis Center')
        ->and(PropertySettings::entityAddress())->toBe('Palo, Leyte')
        ->and(PropertySettings::ppeThreshold())->toBe('50000.00')
        ->and(PropertySettings::splvThreshold())->toBe('5000.00')
        ->and(PropertySettings::icsTagging())->toBe(IcsTagging::All)
        ->and(PropertySettings::paperSize())->toBe(PaperSize::A4)
        ->and(PropertySettings::renewalYears())->toBe(3)
        ->and(PropertySettings::risPattern())->toBe('^\d{8}$')
        ->and(PropertySettings::icsPattern())->toBe('^(SPLV|SPHV)-\d{4}-\d{2}-\d{3}$')
        ->and(PropertySettings::parPattern())->toBe('^\d{4}-\d{2}-\d{3}$');
});

it('renders the property settings page filled with the current settings', function () {
    PropertySettings::setPpeThreshold('75000');

    $this->get(route('configurations.property-settings'))->assertOk();

    Livewire::test(PropertySettingsPage::class)
        ->assertSet('form.ppe_threshold', '75000.00')
        ->assertSet('form.splv_threshold', '5000.00')
        ->assertSet('form.ics_tagging', 'all');
});

it('saves every setting and reads them back normalized', function () {
    Livewire::test(PropertySettingsPage::class)
        ->set('form.entity_name', '  Eastern Visayas Regional Hospital  ')
        ->set('form.entity_address', 'Tacloban City')
        ->set('form.ppe_threshold', '60000.5')
        ->set('form.splv_threshold', '4000')
        ->set('form.ics_tagging', 'sphv_only')
        ->set('form.paper_size', 'legal')
        ->set('form.renewal_years', 5)
        ->set('form.ris_pattern', '^RIS-\d{6}$')
        ->set('form.ics_pattern', '^ICS-\d{4}$')
        ->set('form.par_pattern', '^PAR-\d{4}$')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('property-settings-saved')
        ->assertSet('form.ppe_threshold', '60000.50')
        ->assertSet('form.splv_threshold', '4000.00');

    expect(PropertySettings::entityName())->toBe('Eastern Visayas Regional Hospital')
        ->and(PropertySettings::entityAddress())->toBe('Tacloban City')
        ->and(PropertySettings::ppeThreshold())->toBe('60000.50')
        ->and(PropertySettings::splvThreshold())->toBe('4000.00')
        ->and(PropertySettings::icsTagging())->toBe(IcsTagging::SphvOnly)
        ->and(PropertySettings::paperSize())->toBe(PaperSize::Legal)
        ->and(PropertySettings::renewalYears())->toBe(5)
        ->and(PropertySettings::risPattern())->toBe('^RIS-\d{6}$')
        ->and(PropertySettings::icsPattern())->toBe('^ICS-\d{4}$')
        ->and(PropertySettings::parPattern())->toBe('^PAR-\d{4}$');
});

it('rejects invalid settings and saves nothing', function (string $field, mixed $value, string $message) {
    Livewire::test(PropertySettingsPage::class)
        ->set("form.{$field}", $value)
        ->call('save')
        ->assertHasErrors(["form.{$field}" => $message])
        ->assertNotDispatched('property-settings-saved');

    $this->assertDatabaseCount('settings', 0);
})->with([
    'SPLV equal to PPE' => ['splv_threshold', '50000', 'The SPLV threshold must be less than the PPE threshold.'],
    'SPLV above PPE' => ['splv_threshold', '50000.01', 'The SPLV threshold must be less than the PPE threshold.'],
    'threshold with three decimals' => ['ppe_threshold', '50000.125', 'The PPE threshold field must have 0-2 decimal places.'],
    'threshold that is not a number' => ['ppe_threshold', 'fifty thousand', 'The PPE threshold field must be a number.'],
    'zero threshold' => ['splv_threshold', '0', 'The SPLV threshold field must be greater than 0.'],
    'unknown ICS tagging' => ['ics_tagging', 'some', 'The selected ICS tagging is invalid.'],
    'unknown paper size' => ['paper_size', 'tabloid', 'The selected paper size is invalid.'],
    'renewal period of zero years' => ['renewal_years', 0, 'The renewal period field must be at least 1.'],
    'renewal period over 50 years' => ['renewal_years', 51, 'The renewal period field must not be greater than 50.'],
    'RIS pattern that does not compile' => ['ris_pattern', '(abc', 'The RIS number pattern is not a valid regular expression.'],
    'ICS pattern that does not compile' => ['ics_pattern', '[0-9', 'The ICS number pattern is not a valid regular expression.'],
    'PAR pattern that does not compile' => ['par_pattern', '*PAR', 'The PAR number pattern is not a valid regular expression.'],
]);

it('rejects a PPE threshold lowered to the SPLV threshold', function () {
    Livewire::test(PropertySettingsPage::class)
        ->set('form.ppe_threshold', '5000.00')
        ->call('save')
        ->assertHasErrors(['form.splv_threshold' => 'The SPLV threshold must be less than the PPE threshold.'])
        ->assertNotDispatched('property-settings-saved');

    $this->assertDatabaseCount('settings', 0);
});
