<?php

namespace App\Services\Property;

use App\Models\PropertyTags;
use Carbon\CarbonInterface;

/**
 * What a property tag prints, read live from its ICS/PAR line and document
 * (nothing is copied onto the tag). Keys match
 * resources/views/pdf/partials/property-tag.blade.php, which formats the date
 * and cost and leaves the Inventory Committee Rep. row blank.
 */
class PropertyTagContent
{
    /**
     * @return array{property_number: string, item: string, model: string, serial_number: string, date_acquired: ?CarbonInterface, cost: string, accountable_person: string, document_label: string}
     */
    public static function for(PropertyTags $tag): array
    {
        $tag->loadMissing('line.document');
        $line = $tag->line;
        $document = $line->document;

        return [
            'property_number' => (string) $line->property_number,
            'item' => self::join(', ', [$line->item_name, $line->item_type]),
            'model' => self::join(' / ', [$line->brand, $line->model]),
            'serial_number' => (string) $line->serial_number,
            'date_acquired' => $line->date_acquired,
            'cost' => number_format((float) $line->unit_cost, 2, '.', ''),
            'accountable_person' => (string) $document?->to_name,
            'document_label' => $document?->label() ?? '',
        ];
    }

    /**
     * @param  array<int, string|null>  $parts
     */
    private static function join(string $glue, array $parts): string
    {
        return implode($glue, array_filter($parts, fn (?string $part) => filled($part)));
    }
}
