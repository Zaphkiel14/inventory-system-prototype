<?php

namespace Database\Factories;

use App\Enums\ReceiptStatus;
use App\Models\StockReceipts;
use App\Models\Suppliers;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<StockReceipts>
 */
class StockReceiptsFactory extends Factory
{
    protected $model = StockReceipts::class;

    public function definition(): array
    {
        $received = Carbon::instance(fake()->dateTimeBetween('-6 months', '-1 day'))
            ->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 15, 30, 45]));

        return [
            'supplier_id' => Suppliers::factory(),
            'purchase_order_number' => fake()->boolean(80)
                ? 'PO-'.$received->format('Y').'-'.fake()->unique()->numerify('#####')
                : null,
            'received_by' => User::factory(),
            'reference_number' => 'DR-'.fake()->unique()->numerify('######'),
            'received_date' => $received,
            // Deliveries arrive on, or a day or two before, the day they're logged.
            'delivery_date' => fake()->boolean(85)
                ? $received->copy()->subDays(fake()->numberBetween(0, 2))
                : null,
            'status' => fake()->boolean(80) ? ReceiptStatus::Complete : ReceiptStatus::Partial,
            'remarks' => fake()->boolean(35) ? fake()->randomElement([
                'Delivered in good condition.',
                'Boxes slightly dented, contents intact.',
                'Remaining items to follow next week.',
                'Received after office hours.',
                'Checked against delivery receipt, no discrepancies.',
                'Supplier advised of short delivery.',
            ]) : null,
            'created_at' => $received,
            'updated_at' => $received,
        ];
    }

    public function complete(): static
    {
        return $this->state(['status' => ReceiptStatus::Complete]);
    }

    public function partial(): static
    {
        return $this->state([
            'status' => ReceiptStatus::Partial,
            'remarks' => 'Partial delivery, balance to follow.',
        ]);
    }

    public function receivedOn(Carbon|string $date): static
    {
        $date = Carbon::parse($date);

        return $this->state([
            'received_date' => $date,
            'delivery_date' => $date->copy(),
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
