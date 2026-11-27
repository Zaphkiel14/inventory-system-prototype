<?php

namespace Database\Factories;

use App\Models\StickerSheetProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A non-default copy of the standard A4 2 × 5 layout. Use asDefault() to
 * make it the default (which clears the flag on the others).
 *
 * @extends Factory<StickerSheetProfiles>
 */
class StickerSheetProfilesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...StickerSheetProfiles::DEFAULT_PROFILE,
            'name' => 'Sheet '.fake()->unique()->numerify('###'),
            'is_default' => false,
        ];
    }

    public function asDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }
}
