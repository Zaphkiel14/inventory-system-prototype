<?php

namespace App\Livewire\Configurations;

use App\Livewire\Forms\StickerSheetProfilesForm;
use App\Models\StickerSheetProfiles;
use App\Services\Property\TagSheetLayout;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Create/edit modal for a sticker sheet profile ("sticker-profile-form"),
 * with a live preview of the grid as the sizes are typed.
 *
 * @property-read bool $layoutFits
 * @property-read list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}> $previewSlots
 */
class StickerProfileForm extends Component
{
    public const MODAL = 'sticker-profile-form';

    public StickerSheetProfilesForm $form;

    #[On('add-sticker-profile')]
    public function create(): void
    {
        $this->form->initialize();

        Flux::modal(self::MODAL)->show();
    }

    #[On('edit-sticker-profile')]
    public function edit(int $id): void
    {
        $this->form->load(StickerSheetProfiles::query()->findOrFail($id));

        Flux::modal(self::MODAL)->show();
    }

    public function save(): void
    {
        $isEditing = $this->form->profile !== null;
        $profile = $this->form->store();

        Flux::modal(self::MODAL)->close();

        Flux::toast(
            text: $isEditing ? "{$profile->name} updated." : "{$profile->name} added.",
            heading: 'Sticker profile',
            variant: 'success'
        );

        $this->dispatch('sticker-profile-saved', id: $profile->id);
        $this->form->reset();
    }

    /**
     * Whether the typed grid fits the page (for a warning next to the preview).
     */
    #[Computed]
    public function layoutFits(): bool
    {
        return TagSheetLayout::fits($this->form->unsaved());
    }

    /**
     * Every slot of the typed layout in mm, for the live preview; empty when
     * the grid is unusable (no columns or rows).
     *
     * @return list<array{index: int, row: int, column: int, x_mm: float, y_mm: float, width_mm: float, height_mm: float}>
     */
    #[Computed]
    public function previewSlots(): array
    {
        $profile = $this->form->unsaved();

        if ($profile->columns < 1 || $profile->rows < 1 || $profile->columns > 20 || $profile->rows > 20) {
            return [];
        }

        return TagSheetLayout::calibration($profile);
    }

    public function render(): View
    {
        return view('livewire.configurations.sticker-profile-form');
    }
}
