<?php

namespace App\Livewire\Forms;

use App\Enums\IcsTagging;
use App\Enums\PaperSize;
use App\Support\Money;
use App\Support\PropertySettings;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Edits the property-document settings (App\Support\PropertySettings).
 * Thresholds are kept as strings so they round-trip through inputs exactly.
 */
class PropertySettingsForm extends Form
{
    public string $entity_name = '';

    public string $entity_address = '';

    public string $ppe_threshold = '';

    public string $splv_threshold = '';

    public string $ics_tagging = '';

    public string $paper_size = '';

    public int $renewal_years = 3;

    public string $ris_pattern = '';

    public string $ics_pattern = '';

    public string $par_pattern = '';

    /**
     * Fill the form when the page opens (stored values, else the defaults).
     */
    public function initialize(): void
    {
        $this->resetValidation();
        $this->load();
    }

    /**
     * Fill the form from the current settings.
     */
    public function load(): void
    {
        $this->entity_name = PropertySettings::entityName();
        $this->entity_address = PropertySettings::entityAddress();
        $this->ppe_threshold = PropertySettings::ppeThreshold();
        $this->splv_threshold = PropertySettings::splvThreshold();
        $this->ics_tagging = PropertySettings::icsTagging()->value;
        $this->paper_size = PropertySettings::paperSize()->value;
        $this->renewal_years = PropertySettings::renewalYears();
        $this->ris_pattern = PropertySettings::risPattern();
        $this->ics_pattern = PropertySettings::icsPattern();
        $this->par_pattern = PropertySettings::parPattern();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $amount = ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'];

        return [
            'entity_name' => ['required', 'string', 'max:255'],
            'entity_address' => ['required', 'string', 'max:255'],
            'ppe_threshold' => $amount,
            'splv_threshold' => [...$amount, $this->belowPpeThreshold()],
            'ics_tagging' => ['required', Rule::enum(IcsTagging::class)],
            'paper_size' => ['required', Rule::enum(PaperSize::class)],
            'renewal_years' => ['required', 'integer', 'min:1', 'max:50'],
            'ris_pattern' => ['required', 'string', 'max:255', $this->validPattern()],
            'ics_pattern' => ['required', 'string', 'max:255', $this->validPattern()],
            'par_pattern' => ['required', 'string', 'max:255', $this->validPattern()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'entity_name' => 'entity name',
            'entity_address' => 'entity address',
            'ppe_threshold' => 'PPE threshold',
            'splv_threshold' => 'SPLV threshold',
            'ics_tagging' => 'ICS tagging',
            'paper_size' => 'paper size',
            'renewal_years' => 'renewal period',
            'ris_pattern' => 'RIS number pattern',
            'ics_pattern' => 'ICS number pattern',
            'par_pattern' => 'PAR number pattern',
        ];
    }

    /**
     * Validate and save every setting in one transaction.
     */
    public function store(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            PropertySettings::setEntityName(trim($this->entity_name));
            PropertySettings::setEntityAddress(trim($this->entity_address));
            PropertySettings::setPpeThreshold($this->ppe_threshold);
            PropertySettings::setSplvThreshold($this->splv_threshold);
            PropertySettings::setIcsTagging(IcsTagging::from($this->ics_tagging));
            PropertySettings::setPaperSize(PaperSize::from($this->paper_size));
            PropertySettings::setRenewalYears($this->renewal_years);
            PropertySettings::setRisPattern($this->ris_pattern);
            PropertySettings::setIcsPattern($this->ics_pattern);
            PropertySettings::setParPattern($this->par_pattern);
        });
    }

    /**
     * The SPLV ceiling must sit strictly below the PPE floor, compared in centavos.
     */
    private function belowPpeThreshold(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_numeric($value) || ! is_numeric($this->ppe_threshold)) {
                return;
            }

            if (Money::centavos((string) $value) >= Money::centavos($this->ppe_threshold)) {
                $fail('The :attribute must be less than the PPE threshold.');
            }
        };
    }

    private function validPattern(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && ! PropertySettings::isValidPattern($value)) {
                $fail('The :attribute is not a valid regular expression.');
            }
        };
    }
}
