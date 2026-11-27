<?php

namespace App\Services\Property;

use App\Enums\PropertyDocumentType;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Support\DocumentLogo;
use App\Support\PropertySettings;
use Carbon\CarbonInterface;

/**
 * View data for the ICS (pdf.ics) and PAR (pdf.par) printouts. The keys are
 * the contract those views are built on.
 */
class PropertyDocumentPrint
{
    /**
     * @return array{
     *     logo: ?string, entity_name: string, entity_address: string,
     *     fund_cluster: ?string, document_number: ?string, document_date: CarbonInterface,
     *     show_total: bool, is_draft: bool,
     *     lines: list<array{quantity: int, unit: ?string, unit_cost: string, total_cost: string, item_name: string, item_type: ?string, brand: ?string, model: ?string, serial_number: ?string, property_number: ?string, estimated_useful_life_years: ?int, date_acquired: ?CarbonInterface}>,
     *     from: array{name: ?string, position: ?string, date: ?CarbonInterface},
     *     to: array{name: ?string, position: ?string, date: ?CarbonInterface},
     * }
     */
    public static function for(PropertyDocuments $document): array
    {
        $document->loadMissing(['lines' => fn ($query) => $query->orderBy('id')]);

        $lines = array_values($document->lines->map(fn (PropertyDocumentLines $line) => [
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'unit_cost' => (string) $line->unit_cost,
            'total_cost' => $line->total_cost,
            'item_name' => $line->item_name,
            'item_type' => $line->item_type,
            'brand' => $line->brand,
            'model' => $line->model,
            'serial_number' => $line->serial_number,
            'property_number' => $line->property_number,
            'estimated_useful_life_years' => $line->estimated_useful_life_years,
            'date_acquired' => $line->date_acquired,
        ])->all());

        return [
            'logo' => DocumentLogo::dataUri(),
            'entity_name' => PropertySettings::entityName(),
            'entity_address' => PropertySettings::entityAddress(),
            'fund_cluster' => $document->fund_cluster,
            'document_number' => $document->document_number,
            'document_date' => $document->document_date,
            'show_total' => $document->type === PropertyDocumentType::Ics && count($lines) > 1,
            'is_draft' => ! $document->isFinalized(),
            'lines' => $lines,
            'from' => [
                'name' => $document->from_name,
                'position' => $document->from_position,
                'date' => $document->from_date,
            ],
            'to' => [
                'name' => $document->to_name,
                'position' => $document->to_position,
                'date' => $document->to_date,
            ],
        ];
    }
}
