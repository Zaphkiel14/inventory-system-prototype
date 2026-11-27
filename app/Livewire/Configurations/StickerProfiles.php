<?php

namespace App\Livewire\Configurations;

use App\Models\StickerSheetProfiles;
use App\Services\Property\TagSheetLayout;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Sticker sheet profiles: list, make default, delete (only when never used
 * for a print). Create/edit happens in StickerProfileForm.
 *
 * Each row carries prints_count and slots_per_sheet.
 *
 * @property-read Collection<int, StickerSheetProfiles> $profiles
 */
class StickerProfiles extends Component
{
    #[On('sticker-profile-saved')]
    public function refreshProfiles(): void
    {
        unset($this->profiles);
    }

    /**
     * @return Collection<int, StickerSheetProfiles>
     */
    #[Computed]
    public function profiles(): Collection
    {
        $profiles = StickerSheetProfiles::query()
            ->withCount('prints')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $profiles->each(fn (StickerSheetProfiles $profile) => $profile->setAttribute('slots_per_sheet', TagSheetLayout::slotsPerSheet($profile)));

        return $profiles;
    }

    public function makeDefault(int $id): void
    {
        $profile = StickerSheetProfiles::query()->findOrFail($id);
        $profile->update(['is_default' => true]);

        unset($this->profiles);

        Flux::toast(text: "{$profile->name} is now the default sheet.", heading: 'Sticker profile', variant: 'success');
        $this->dispatch('sticker-profile-saved', id: $profile->id);
    }

    public function delete(int $id): void
    {
        $profile = StickerSheetProfiles::query()->withCount('prints')->findOrFail($id);

        $refusal = match (true) {
            $profile->prints_count > 0 => "{$profile->name} was used for {$profile->prints_count} print(s), so it is kept for the print log.",
            $profile->is_default => "{$profile->name} is the default sheet. Make another profile the default first.",
            default => null,
        };

        if ($refusal !== null) {
            Flux::toast(text: $refusal, heading: 'Cannot delete', variant: 'danger');

            return;
        }

        $profile->delete();
        unset($this->profiles);

        Flux::toast(text: "{$profile->name} deleted.", heading: 'Sticker profile', variant: 'success');
        $this->dispatch('sticker-profile-saved', id: $profile->id);
    }

    public function render(): View
    {
        return view('livewire.configurations.sticker-profiles');
    }
}
