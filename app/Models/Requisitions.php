<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use App\Notifications\RequisitionStatusUpdated;
use App\Services\Inventory\BatchCodeGenerator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;

class Requisitions extends Model
{
    protected $fillable = [
        'request_number',
        'tracking_code',
        'user_id',
        'requester_name',
        'supervisor_name',
        'requester_email',
        'administrative_area_id',
        'designation',
        'status',
        'purpose',
        'submitted_at',
        'cancelled_at',
    ];

    protected static function booted(): void
    {
        // Every request gets a public tracking code, and a requester name even
        // when it was created for a signed-in user rather than from the public form.
        static::creating(function (self $requisition) {
            $requisition->tracking_code ??= app(BatchCodeGenerator::class)->trackingCode();

            if (blank($requisition->getAttributes()['requester_name'] ?? null)) {
                $requisition->requester_name = (string) $requisition->requester?->name;
            }
        });

        // One hook covers approval, rejection, issuance and cancellation.
        static::updated(function (self $requisition) {
            if ($requisition->wasChanged('status') && filled($requisition->requester_email)) {
                Notification::route('mail', $requisition->requester_email)
                    ->notify(new RequisitionStatusUpdated($requisition));
            }
        });
    }

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

    /** @return HasMany<RequisitionItems, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItems::class, 'requisition_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approvals::class, 'requisition_id');
    }

    /**
     * The decision on this requisition (a requisition is decided once).
     */
    public function latestApproval(): HasOne
    {
        return $this->hasOne(Approvals::class, 'requisition_id')->latestOfMany('decided_at');
    }

    public function stockIssuances(): HasMany
    {
        return $this->hasMany(StockIssuances::class, 'requisition_id');
    }

    /**
     * Who asked for this: the name typed on the public form, falling back to
     * the linked account.
     */
    protected function requesterName(): Attribute
    {
        return Attribute::get(fn (?string $value) => filled($value) ? $value : ($this->requester?->name ?? '—'));
    }

    public function isCancellableBy(?User $user): bool
    {
        return $user !== null
            && $this->user_id === $user->id
            && $this->status?->isCancellable();
    }
}
