<?php

namespace App\Livewire\Forms;

use App\Enums\ApprovalDecision;
use App\Enums\RequisitionStatus;
use App\Models\Approvals;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Decides a pending requisition: the approver sets how much of each requested
 * item may be dispensed, or rejects the request outright. Approval never moves
 * stock — it only commits quantities that issuance will later release.
 */
class ApprovalsForm extends Form
{
    public ?int $requisition_id = null;

    /** @var array<int|string, mixed> requisition_item_id => approved quantity */
    public array $approved = [];

    public string $remarks = '';

    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /**
     * Load a requisition, pre-filling each line with the most that can be
     * approved: what was requested, capped by uncommitted stock.
     */
    public function load(Requisitions $requisition): void
    {
        $this->initialize();
        $this->requisition_id = $requisition->id;

        $stock = self::stockFor($requisition->items->pluck('inventory_item_id'));

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

    public function approve(): Requisitions
    {
        $this->validate([
            'approved' => ['required', 'array'],
            'approved.*' => ['required', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ], attributes: ['approved.*' => 'approved quantity']);

        return DB::transaction(function () {
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

            $requisition->update(['status' => RequisitionStatus::Approved]);

            $this->record($requisition, ApprovalDecision::Approved);

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
