<?php

namespace App\Services\Property;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentType;
use App\Models\InventoryItems;
use App\Support\Money;
use App\Support\PropertySettings;

/**
 * Decides how an issued unit is accounted for from its item's property flag
 * and its unit cost, using the thresholds in PropertySettings. Amounts are
 * compared as integer centavos, so the boundaries are exact.
 */
class PropertyClassifier
{
    /**
     * Not property → Consumable; cost ≥ PPE threshold → PPE;
     * cost ≤ SPLV threshold → SPLV; anything between → SPHV.
     */
    public function classify(InventoryItems $item, string|float $unitCost): PropertyClass
    {
        if (! $item->is_property) {
            return PropertyClass::Consumable;
        }

        $cost = Money::centavos($unitCost);

        if ($cost >= Money::centavos(PropertySettings::ppeThreshold())) {
            return PropertyClass::Ppe;
        }

        if ($cost <= Money::centavos(PropertySettings::splvThreshold())) {
            return PropertyClass::Splv;
        }

        return PropertyClass::Sphv;
    }

    /**
     * Classify a cost as if it were a property item, for hand-encoded ICS/PAR
     * lines that have no inventory item.
     */
    public function classifyCost(string|float $unitCost): PropertyClass
    {
        return $this->classify(new InventoryItems(['is_property' => true]), $unitCost);
    }

    /**
     * The document a unit of this class belongs on: PAR for PPE, ICS for
     * semi-expendables, none for consumables (RIS only).
     */
    public function suggestedForm(PropertyClass $class): ?PropertyDocumentType
    {
        return match ($class) {
            PropertyClass::Ppe => PropertyDocumentType::Par,
            PropertyClass::Sphv, PropertyClass::Splv => PropertyDocumentType::Ics,
            PropertyClass::Consumable => null,
        };
    }
}
