<?php

namespace App\Livewire\Forms;

use App\Enums\ApprovalDecision;
use App\Enums\AuditAction;
use App\Enums\RequisitionStatus;
use App\Models\Approvals;
use App\Models\AuditLogs;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Services\AdministrativeAreas\AdministrativeAreaService;
use App\Support\PropertySettings;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;
use Throwable;

/**
 * Decides a pending requisition: the approver sets how much of each requested
 * item may be dispensed, or rejects the request outright. Approval never moves
 * stock — it only commits quantities that issuance will later release.
 *
 * Approving also fills in the RIS header (RIS No., Fund Cluster, Division,
 * Office, RC Code and the Approved-by signatory).
 */
class ApprovalsForm extends Form
{
    /** Set by load() only. */
    #[Locked]
    public ?int $requisition_id = null;

    /** @var array<int|string, mixed> requisition_item_id => approved quantity */
    public array $approved = [];

    public string $remarks = '';

    public string $ris_number = '';

    /** Free text; there is no fund cluster table yet. */
    public string $fund_cluster = '';

    /** @var int|string|null administrative area id (any level) */
    public $division_area_id = null;

    /** @var int|string|null administrative area id (any level) */
    public $office_area_id = null;

    public string $rc_code = '';

    public string $approved_by_name = '';

    public string $approved_by_designation = '';

    /** Y-m-d; defaults to today. */
    public string $approved_on = '';

    /** Needed (min 5 chars) only when ris_number doesn't match the RIS pattern; written to the audit log. */
    public string $pattern_override_reason = '';

    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /**
     * Load a requisition, pre-filling each line with the most that can be
     * approved: what was requested, capped by uncommitted stock.
     *
     * Pass $stock (from stockFor()) when the caller already has it.
     *
     * @param  Collection<int, array{on_hand: int, committed: int, free: int}>|null  $stock
     */
    public function load(Requisitions $requisition, ?Collection $stock = null): void
    {
        $this->initialize();
        $this->requisition_id = $requisition->id;

        $stock ??= self::stockFor($requisition->items->pluck('inventory_item_id'));

        $this->ris_number = (string) $requisition->ris_number;
        $this->fund_cluster = (string) $requisition->fund_cluster;
        $this->division_area_id = $requisition->division_area_id;
        $this->office_area_id = $requisition->office_area_id;
        $this->rc_code = (string) $requisition->rc_code;
        $this->approved_by_name = $requisition->approved_by_name ?? (string) Auth::user()?->name;
        $this->approved_by_designation = (string) $requisition->approved_by_designation;
        $this->approved_on = ($requisition->approved_on ?? today())->format('Y-m-d');

        if ($this->rc_code === '') {
            $this->suggestRcCode();
        }

        foreach ($requisition->items as $line) {
            $this->approved[$line->id] = $line->approved_qty
                ?? min($line->requested_qty, $stock[$line->inventory_item_id]['free'] ?? 0);
        }
    }

    /**
     * Current stock picture per item:
     *  on_hand   usable stock (unexpired batches with stock left)
     *  committed approved for other requisitions but not yet released
     *  free      what can still be promised (on_hand - committed, never < 0)
     *
     * @param  iterable<int>  $itemIds
     * @return Collection<int, array{on_hand: int, committed: int, free: int}>
     */
    public static function stockFor(iterable $itemIds): Collection
    {
        return InventoryItems::query()
            ->whereKey(collect($itemIds)->unique()->values())
            ->withAvailableQty()
            ->withCommittedQty()
            ->get()
            ->mapWithKeys(function (InventoryItems $item) {
                $onHand = (int) $item->available_qty;
                $committed = (int) $item->committed_qty;

                return [$item->id => [
                    'on_hand' => $onHand,
                    'committed' => $committed,
                    'free' => max(0, $onHand - $committed),
                ]];
            });
    }

    /**
     * Pre-fill the RC Code from the latest other requisition for the same
     * office that has one. Leaves it empty when there is none.
     */
    public function suggestRcCode(): void
    {
        if (blank($this->office_area_id)) {
            return;
        }

        $this->rc_code = (string) Requisitions::query()
            ->where('office_area_id', (int) $this->office_area_id)
            ->whereNotNull('rc_code')
            ->where('rc_code', '!=', '')
            ->when($this->requisition_id, fn ($query) => $query->whereKeyNot($this->requisition_id))
            ->latest('id')
            ->value('rc_code');
    }

    /**
     * Whether the typed RIS No. is filled in but doesn't match the RIS pattern
     * setting (saving then needs an override reason).
     */
    public function risPatternMismatch(): bool
    {
        return filled($this->ris_number)
            && ! PropertySettings::matchesPattern(PropertySettings::risPattern(), trim($this->ris_number));
    }

    public function approve(): Requisitions
    {
        $this->ris_number = trim($this->ris_number);

        $this->validate([
            'approved' => ['required', 'array'],
            'approved.*' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'ris_number' => [
                'required', 'string', 'max:255',
                Rule::unique('requisitions', 'ris_number')->ignore($this->requisition_id),
                $this->matchesRisPattern(),
            ],
            'pattern_override_reason' => ['nullable', 'string', 'min:5', 'max:1000'],
            'fund_cluster' => ['required', 'string', 'max:255'],
            'division_area_id' => ['nullable', 'integer', $this->knownArea()],
            'office_area_id' => ['nullable', 'integer', $this->knownArea()],
            'rc_code' => ['nullable', 'string', 'max:255'],
            'approved_by_name' => ['required', 'string', 'max:255'],
            'approved_by_designation' => ['required', 'string', 'max:255'],
            'approved_on' => ['required', 'date'],
        ], attributes: [
            'approved.*' => 'approved quantity',
            'ris_number' => 'RIS No.',
            'pattern_override_reason' => 'override reason',
            'fund_cluster' => 'fund cluster',
            'division_area_id' => 'division',
            'office_area_id' => 'office',
            'rc_code' => 'RC code',
            'approved_by_name' => 'approved by (name)',
            'approved_by_designation' => 'approved by (designation)',
            'approved_on' => 'approval date',
        ]);

        $header = $this->risHeader();
        $overridden = $this->risPatternMismatch();

        return DB::transaction(function () use ($header, $overridden) {
            $requisition = $this->lockPending();
            $lines = $requisition->items()->lockForUpdate()->get();

            // Re-check stock inside the transaction so two approvers can't
            // promise the same units at the same time.
            $stock = self::stockFor($lines->pluck('inventory_item_id'));

            $errors = [];
            $total = 0;

            foreach ($lines as $line) {
                $key = "form.approved.{$line->id}";

                if (! array_key_exists($line->id, $this->approved)) {
                    $errors[$key] = 'Enter an approved quantity.';

                    continue;
                }

                $qty = (int) $this->approved[$line->id];
                $free = $stock[$line->inventory_item_id]['free'] ?? 0;

                if ($qty > $line->requested_qty) {
                    $errors[$key] = 'Cannot approve more than the '.number_format($line->requested_qty).' requested.';
                } elseif ($qty > $free) {
                    $errors[$key] = 'Only '.number_format($free).' are free to approve.';
                }

                $total += $qty;
            }

            if ($errors === [] && $total === 0) {
                $errors['form.approved'] = 'Approve at least one item, or reject the request instead.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            foreach ($lines as $line) {
                $qty = (int) $this->approved[$line->id];

                $line->update([
                    'approved_qty' => $qty,
                    'outstanding_qty' => $qty - $line->released_qty,
                ]);
            }

            $requisition->update(['status' => RequisitionStatus::Approved, ...$header]);

            $this->record($requisition, ApprovalDecision::Approved);

            if ($overridden) {
                AuditLogs::create([
                    'user_id' => Auth::id(),
                    'action' => AuditAction::RisPatternOverride,
                    'auditable_type' => Requisitions::class,
                    'auditable_id' => $requisition->id,
                    'old_values' => [],
                    'new_values' => ['ris_number' => $header['ris_number']],
                    'reason' => trim($this->pattern_override_reason),
                ]);
            }

            return $requisition;
        });
    }

    public function reject(): Requisitions
    {
        $this->validate(
            ['remarks' => ['required', 'string', 'max:255']],
            ['remarks.required' => 'Give a reason for rejecting this request.'],
        );

        return DB::transaction(function () {
            $requisition = $this->lockPending();

            // Nothing is approved, so nothing is outstanding for issuance.
            $requisition->items()->update(['approved_qty' => 0, 'outstanding_qty' => 0]);
            $requisition->update(['status' => RequisitionStatus::Rejected]);

            $this->record($requisition, ApprovalDecision::Rejected);

            return $requisition;
        });
    }

    /**
     * The RIS columns written on approval, with Division/Office labels
     * snapshotted from the administrative-areas API.
     *
     * @return array<string, mixed>
     */
    protected function risHeader(): array
    {
        $areas = app(AdministrativeAreaService::class);
        $division = filled($this->division_area_id) ? $areas->find((int) $this->division_area_id) : null;
        $office = filled($this->office_area_id) ? $areas->find((int) $this->office_area_id) : null;

        return [
            'ris_number' => $this->ris_number,
            'fund_cluster' => trim($this->fund_cluster),
            'division_area_id' => $division['id'] ?? null,
            'division' => $division !== null ? $areas->shortLabel($division) : null,
            'office_area_id' => $office['id'] ?? null,
            'office' => $office !== null ? $areas->shortLabel($office) : null,
            'rc_code' => filled($this->rc_code) ? trim($this->rc_code) : null,
            'approved_by_name' => trim($this->approved_by_name),
            'approved_by_designation' => trim($this->approved_by_designation),
            'approved_on' => $this->approved_on,
        ];
    }

    /**
     * A RIS No. off the pattern is allowed only with an override reason.
     */
    protected function matchesRisPattern(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->risPatternMismatch() && blank($this->pattern_override_reason)) {
                $fail('The RIS No. does not match the expected format ('.PropertySettings::risPattern().'). Check it, or give an override reason to save it anyway.');
            }
        };
    }

    /**
     * The area must exist in the administrative-areas API (any level).
     */
    protected function knownArea(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            try {
                $area = app(AdministrativeAreaService::class)->find((int) $value);
            } catch (Throwable) {
                $fail('Administrative areas could not be loaded. Please try again later.');

                return;
            }

            if ($area === null) {
                $fail('Select a valid :attribute.');
            }
        };
    }

    protected function lockPending(): Requisitions
    {
        $requisition = Requisitions::query()->lockForUpdate()->find($this->requisition_id);

        if ($requisition?->status !== RequisitionStatus::Pending) {
            throw ValidationException::withMessages([
                'form.requisition_id' => 'This request is no longer pending — it was cancelled or already decided.',
            ]);
        }

        return $requisition;
    }

    protected function record(Requisitions $requisition, ApprovalDecision $decision): Approvals
    {
        return $requisition->approvals()->create([
            'user_id' => Auth::id(),
            'decision' => $decision,
            'remarks' => filled($this->remarks) ? trim($this->remarks) : null,
            'decided_at' => now(),
        ]);
    }
}
