<?php

namespace Database\Factories;

use App\Models\StockIssuances;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An issuance header only. It has no lines or batch draws and writes no
 * ledger rows, so it's for tests that need an issuance to exist (lists,
 * documents, PDFs), not for testing stock movement. Issuances are
 * append-only: create them, never update or delete them.
 *
 * @extends Factory<StockIssuances>
 */
class StockIssuancesFactory extends Factory
{
    protected $model = StockIssuances::class;

    public function definition(): array
    {
        return [
            'requisition_id' => RequisitionsFactory::new()->partiallyIssued(),
            'user_id' => User::factory(),
            'issuance_number' => fake()->unique()->numerify('ISS-FAC-######'),
            'released_by' => fake()->name(),
            'received_by' => fake()->name(),
            'released_at' => now(),
            'remarks' => null,
        ];
    }
}
