<?php

namespace Database\Factories;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Models\PropertyDocuments;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A draft SPLV ICS by default, with no source requisition or issuance (as if
 * typed in by hand). Use par(), ics() and finalized() to vary it.
 *
 * @extends Factory<PropertyDocuments>
 */
class PropertyDocumentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PropertyDocumentType::Ics,
            'ics_class' => PropertyClass::Splv,
            'document_number' => null,
            'fund_cluster' => '01',
            'document_date' => today()->toDateString(),
            'requisition_id' => null,
            'stock_issuance_id' => null,
            'iar_number' => null,
            'from_name' => fake()->name(),
            'from_position' => 'Supply Officer',
            'from_date' => today()->toDateString(),
            'to_name' => fake()->name(),
            'to_position' => fake()->jobTitle(),
            'to_date' => today()->toDateString(),
            'status' => PropertyDocumentStatus::Draft,
            'finalized_at' => null,
            'renewal_due_on' => null,
        ];
    }

    /**
     * An ICS of the given semi-expendable class (Splv or Sphv).
     */
    public function ics(PropertyClass $class = PropertyClass::Splv): static
    {
        return $this->state(fn (): array => [
            'type' => PropertyDocumentType::Ics,
            'ics_class' => $class,
        ]);
    }

    public function par(): static
    {
        return $this->state(fn (): array => [
            'type' => PropertyDocumentType::Par,
            'ics_class' => null,
        ]);
    }

    /**
     * Finalized and active, with a document number in the default pattern
     * and renewal due three years after the document date.
     */
    public function finalized(): static
    {
        return $this->state(fn (array $attributes): array => [
            'document_number' => $attributes['document_number'] ?? $this->documentNumber($attributes),
            'status' => PropertyDocumentStatus::Active,
            'finalized_at' => now(),
            'renewal_due_on' => today()->addYears(3)->toDateString(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function documentNumber(array $attributes): string
    {
        $sequence = fake()->unique()->numerify('###');
        $period = now()->format('Y-m');

        if ($attributes['type'] === PropertyDocumentType::Par) {
            return "{$period}-{$sequence}";
        }

        $prefix = $attributes['ics_class'] === PropertyClass::Sphv ? 'SPHV' : 'SPLV';

        return "{$prefix}-{$period}-{$sequence}";
    }
}
