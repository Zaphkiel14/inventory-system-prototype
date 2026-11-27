<?php

namespace App\Livewire\Configurations;

use App\Enums\IcsTagging;
use App\Enums\PaperSize;
use App\Livewire\Forms\PropertySettingsForm;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Property document settings: entity details, classification thresholds,
 * ICS tagging, paper size, renewal period and control-number patterns.
 */
class PropertySettings extends Component
{
    public PropertySettingsForm $form;

    public function mount(): void
    {
        $this->form->initialize();
    }

    public function save(): void
    {
        $this->form->store();
        $this->form->load();

        Flux::toast(
            text: 'Property settings saved successfully',
            heading: 'Success',
            variant: 'success'
        );

        $this->dispatch('property-settings-saved');
    }

    /**
     * @return array<string, string> enum value => label
     */
    #[Computed]
    public function icsTaggingOptions(): array
    {
        return collect(IcsTagging::cases())
            ->mapWithKeys(fn (IcsTagging $case) => [$case->value => $case->label()])
            ->all();
    }

    /**
     * @return array<string, string> enum value => label
     */
    #[Computed]
    public function paperSizeOptions(): array
    {
        return collect(PaperSize::cases())
            ->mapWithKeys(fn (PaperSize $case) => [$case->value => $case->label()])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.configurations.property-settings');
    }
}
