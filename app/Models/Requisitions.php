<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requisitions extends Model
{
    protected $fillable = [
        'request_number',
        'user_id',
        'department_id',
        'section_id',
        'unit_id',
        'status',
        'purpose',
        'submitted_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RequisitionStatus::class,
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItems::class, 'requisition_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approvals::class, 'requisition_id');
    }

    public function stockIssuances(): HasMany
    {
        return $this->hasMany(StockIssuances::class, 'requisition_id');
    }

    public function isCancellableBy(?User $user): bool
    {
        return $user !== null
            && $this->user_id === $user->id
            && $this->status?->isCancellable();
    }
}
