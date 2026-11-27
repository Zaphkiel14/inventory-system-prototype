<?php

namespace App\Livewire\PropertyManagement;

use App\Enums\PropertyDocumentType;

/**
 * Property Acknowledgment Receipts (PAR, Appendix 71): PPE at or above the
 * PPE threshold.
 */
class PropertyAcknowledgmentReceipts extends PropertyDocumentList
{
    public function documentType(): PropertyDocumentType
    {
        return PropertyDocumentType::Par;
    }

    /**
     * @return view-string
     */
    protected function viewName(): string
    {
        return 'livewire.property-management.property-acknowledgement-receipt';
    }
}
