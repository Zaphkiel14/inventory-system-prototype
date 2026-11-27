<?php

namespace App\Services\Property;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentType;
use App\Models\IssuedPropertyUnits;
use App\Models\PropertyDocuments;
use App\Models\StockIssuances;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns an issuance's property units into draft custody documents: one ICS
 * for SPLV units, one ICS for SPHV units and one PAR for PPE units (only the
 * ones that apply). Never moves stock.
 *
 * Idempotent: units already on a line are skipped, and new units join the
 * issuance's existing draft of the same kind instead of starting another.
 */
class PropertyDocumentDrafter
{
    public function __construct(private PropertyClassifier $classifier) {}

    /**
     * Draft (or top up) the issuance's documents and return its drafts:
     * ICS SPLV, then ICS SPHV, then PAR.
     *
     * @return Collection<int, PropertyDocuments>
     */
    public function forIssuance(StockIssuances $issuance): Collection
    {
        return DB::transaction(function () use ($issuance): Collection {
            // Serializes two drafters on the same issuance (a read lock only;
            // issuances themselves stay append-only).
            StockIssuances::query()->whereKey($issuance->id)->lockForUpdate()->first();

            $units = IssuedPropertyUnits::query()
                ->forIssuance($issuance->id)
                ->withoutDocument()
                ->with(['inventoryItem', 'stockIssuanceBatch.inventoryBatch'])
                ->orderBy('id')
                ->get();

            foreach ($units->groupBy(fn (IssuedPropertyUnits $unit) => $unit->property_class->value) as $classValue => $group) {
                $class = PropertyClass::from($classValue);
                $type = $this->classifier->suggestedForm($class);

                if ($type === null) {
                    continue;
                }

                $document = $this->draftFor($issuance, $type, $type === PropertyDocumentType::Ics ? $class : null);

                foreach ($group as $unit) {
                    $document->lines()->create([
                        'issued_property_unit_id' => $unit->id,
                        'inventory_item_id' => $unit->inventory_item_id,
                        'quantity' => 1,
                        'unit' => $unit->inventoryItem?->unit,
                        'unit_cost' => $unit->unit_cost,
                        'item_name' => (string) $unit->inventoryItem?->name,
                        'brand' => $unit->brand,
                        'model' => $unit->model,
                        'serial_number' => $unit->serial_number,
                        // Left empty: property_number is unique across all lines, so
                        // the LV/HV prefix is pre-filled by the form, not stored.
                        'property_number' => null,
                        'estimated_useful_life_years' => $unit->inventoryItem?->default_useful_life_years,
                        'date_acquired' => $unit->stockIssuanceBatch?->inventoryBatch?->received_at?->toDateString(),
                    ]);
                }
            }

            return PropertyDocuments::query()
                ->drafts()
                ->where('stock_issuance_id', $issuance->id)
                ->with('lines')
                ->get()
                ->sortBy(fn (PropertyDocuments $document) => match (true) {
                    $document->ics_class === PropertyClass::Splv => 0,
                    $document->ics_class === PropertyClass::Sphv => 1,
                    default => 2,
                })
                ->values();
        });
    }

    /**
     * The issuance's open draft of this kind, or a new one with the header
     * pre-filled from the issuance and its requisition.
     */
    private function draftFor(StockIssuances $issuance, PropertyDocumentType $type, ?PropertyClass $icsClass): PropertyDocuments
    {
        $existing = PropertyDocuments::query()
            ->drafts()
            ->where('stock_issuance_id', $issuance->id)
            ->where('type', $type)
            ->where('ics_class', $icsClass?->value)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $releasedOn = $issuance->released_at->toDateString();

        return PropertyDocuments::create([
            'type' => $type,
            'ics_class' => $icsClass,
            'document_number' => null,
            'fund_cluster' => $issuance->requisition?->fund_cluster,
            'document_date' => today()->toDateString(),
            'requisition_id' => $issuance->requisition_id,
            'stock_issuance_id' => $issuance->id,
            'from_name' => $issuance->released_by,
            'from_position' => $issuance->released_by_designation,
            'from_date' => $releasedOn,
            'to_name' => $issuance->received_by,
            'to_position' => $issuance->received_by_designation,
            'to_date' => $releasedOn,
        ]);
    }
}
