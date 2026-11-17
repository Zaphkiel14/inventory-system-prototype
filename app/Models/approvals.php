<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approvals extends Model
{
    protected $fillable = [
        'requisition_id',
        'user_id',
        'decision',
        'remarks',
        'decided_at',
    ];

    public function requisition(): BelongsTo //
    {
        return $this->belongsTo(Requisitions::class, 'requisition_id');
    }
}
