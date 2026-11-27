<?php

namespace App\Models;

use App\Enums\RequisitionStatus;
use App\Notifications\RequisitionStatusUpdated;
use App\Services\Inventory\BatchCodeGenerator;
use Carbon\CarbonImmutable;
use Database\Factories\RequisitionsFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Notification;

/**
 * A supply request. Printed as the RIS (Appendix 63) once approved.
 *
 * @property int $id
 * @property string $request_number
 * @property string $tracking_code
 * @property int|null $user_id
 * @property string $requester_name
 * @property string|null $requester_email
 * @property string|null $requester_position
 * @property RequisitionStatus $status
 * @property string $purpose
 * @property CarbonImmutable $submitted_at
 * @property CarbonImmutable|null $cancelled_at
 * @property string|null $ris_number
 * @property string|null $fund_cluster
 * @property int|null $division_area_id
 * @property string|null $division
 * @property int|null $office_area_id
 * @property string|null $office
 * @property string|null $rc_code
 * @property string|null $approved_by_name
 * @property string|null $approved_by_designation
 * @property CarbonImmutable|null $approved_on
 */
class Requisitions extends Model
{
    /** @use HasFactory<RequisitionsFactory> */
    use HasFactory;

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
        'ris_number',
        'fund_cluster',
        'division_area_id',
        'division',
        'office_area_id',
        'office',
        'rc_code',
        'requester_position',
        'approved_by_name',
        'approved_by_designation',
        'approved_on',
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
            'division_area_id' => 'integer',
            'office_area_id' => 'integer',
            'approved_on' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<RequisitionItems, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItems::class, 'requisition_id');
    }

    /** @return HasMany<Approvals, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(Approvals::class, 'requisition_id');
    }

    /**
     * The decision on this requisition (a requisition is decided once).
     *
     * @return HasOne<Approvals, $this>
     */
    public function latestApproval(): HasOne
    {
        return $this->hasOne(Approvals::class, 'requisition_id')->latestOfMany('decided_at');
    }

    /** @return HasMany<StockIssuances, $this> */
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
            && $this->status->isCancellable();
    }
}
