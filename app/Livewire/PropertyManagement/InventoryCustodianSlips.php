<?php

namespace App\Livewire\PropertyManagement;

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentType;
use Livewire\Attributes\Computed;

/**
 * Inventory Custodian Slips (ICS, Appendix 59): semi-expendable property,
 * one slip per class (SPLV or SPHV).
 */
class InventoryCustodianSlips extends PropertyDocumentList
{
    public function documentType(): PropertyDocumentType
    {
        return PropertyDocumentType::Ics;
    }

    /**
     * Classes a new hand-encoded ICS can be created for (value => label).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function icsClassOptions(): array
    {
        return [
            PropertyClass::Splv->value => PropertyClass::Splv->label(),
            PropertyClass::Sphv->value => PropertyClass::Sphv->label(),
        ];
    }

    /**
     * @return view-string
     */
    protected function viewName(): string
    {
        return 'livewire.property-management.inventory-custodian-slip';
    }
}
