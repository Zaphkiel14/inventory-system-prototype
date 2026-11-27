<?php

namespace Database\Factories;

use App\Enums\PropertyTagStatus;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyTags;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A Pending tag on a new line (of a draft SPLV ICS by default). Use printed()
 * for a tag that has been printed.
 *
 * @extends Factory<PropertyTags>
 */
class PropertyTagsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_document_line_id' => PropertyDocumentLines::factory(),
            'status' => PropertyTagStatus::Pending,
            'print_count' => 0,
            'last_printed_at' => null,
            'last_printed_by' => null,
        ];
    }

    public function printed(int $times = 1): static
    {
        return $this->state(fn (): array => [
            'status' => PropertyTagStatus::Printed,
            'print_count' => $times,
            'last_printed_at' => now(),
        ]);
    }
}
