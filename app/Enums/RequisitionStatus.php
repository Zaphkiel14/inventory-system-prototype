<?php

namespace App\Enums;

/**
 * Lifecycle of a requisition across the request → approval → issuance stages.
 * The request page only creates Pending requests and can Cancel them; the
 * other states are set by the approval and issuance pages.
 */
enum RequisitionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case PartiallyIssued = 'partially_issued';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::PartiallyIssued => 'Partially Issued',
            self::Issued => 'Issued',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'sky',
            self::Rejected => 'red',
            self::PartiallyIssued => 'violet',
            self::Issued => 'green',
            self::Cancelled => 'zinc',
        };
    }

    /**
     * A request can only be withdrawn before anyone has acted on it.
     */
    public function isCancellable(): bool
    {
        return $this === self::Pending;
    }
}
