<?php

namespace App\Models;

use App\Enums\ApprovalDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * A recorded approval decision. Decisions are permanent: they are the audit
 * trail of who approved or rejected a requisition, so they are never edited.
 */
class Approvals extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'decision',
        'remarks',
        'decided_at',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Approval decisions are permanent and cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException('Approval decisions are permanent and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
