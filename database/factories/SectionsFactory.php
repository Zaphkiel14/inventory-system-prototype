<?php

namespace Database\Factories;

use App\Models\Departments;
use App\Models\Sections;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sections>
 */
class SectionsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'code' => collect(explode(' ', $name))
                ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                ->implode(''),
            'department_id' => Departments::factory(),
        ];
    }
}
