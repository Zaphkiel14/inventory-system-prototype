<?php

namespace App\Enums;

/**
 * What an audit_logs row records.
 */
enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Finalized = 'finalized';
    case StatusChanged = 'status_changed';
    case RisPatternOverride = 'ris_pattern_override';
    case DocumentPatternOverride = 'document_pattern_override';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Finalized => 'Finalized',
            self::StatusChanged => 'Status changed',
            self::RisPatternOverride => 'RIS No. pattern override',
            self::DocumentPatternOverride => 'Document No. pattern override',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'green',
            self::Updated => 'sky',
            self::Deleted => 'red',
            self::Finalized => 'indigo',
            self::StatusChanged => 'violet',
            self::RisPatternOverride, self::DocumentPatternOverride => 'amber',
        };
    }
}
