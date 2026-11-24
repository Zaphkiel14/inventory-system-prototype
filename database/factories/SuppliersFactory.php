<?php

namespace Database\Factories;

use App\Models\Suppliers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suppliers>
 */
class SuppliersFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' '.fake()->companySuffix(),
            'contact_person' => fake()->name(),
            'contact' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
