<?php

namespace App\Livewire\Forms;

use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Services\Inventory\BatchAllocator;
use App\Services\Inventory\BatchCodeGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

/**
 * Releases approved quantities. This is the only requisition stage that moves
 * stock: each released line is drawn from batches (FEFO for items with an
 * expiry date, FIFO otherwise) and logged as an inventory transaction.
 * A requisition can be released over several issuances until nothing is
 * outstanding.
 */
class IssuancesForm extends Form
{
    /** Requisition statuses that still have approved stock to release. */
    public const RELEASABLE = [RequisitionStatus::Approved, RequisitionStatus::PartiallyIssued];

    public ?int $requisition_id = null;

    /** @var array<int|string, mixed> requisition_item_id => quantity to release now */
    public array $release = [];

    public string $received_by = '';

    public string $remarks = '';

    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    /**
     * Pre-fill each line with everything outstanding, capped by usable stock.
     */
    public function load(Requisitions $requisition): void
    {
        $this->initialize();
        $this->requisition_id = $requisition->id;
        $this->received_by = $requisition->requester_name === '—' ? '' : $requisition->requester_name;

        $onHand = InventoryItems::query()
            ->whereKey($requisition->items->pluck('inventory_item_id'))
            ->withAvailableQty()
            ->pluck('available_qty', 'id');

        foreach ($requisition->items as $line) {
            if ($line->outstanding_qty > 0) {
                $this->release[$line->id] = min($line->outstanding_qty, (int) ($onHand[$line->inventory_item_id] ?? 0));
            }
        }
    }

    /**
     * Field-level checks, run before the release confirmation is shown and
     * again by store(). Stock availability is only checked inside store().
     */
    public function validateInput(): void
    {
        $this->validate([
            'release' => ['required', 'array'],
            'release.*' => ['required', 'integer', 'min:0'],
            'received_by' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], attributes: ['release.*' => 'release quantity', 'received_by' => 'received by']);
    }

    public function store(): StockIssuances
    {
        $this->validateInput();

        $allocator = app(BatchAllocator::class);
        $codes = app(BatchCodeGenerator::class);

        return DB::transaction(function () use ($allocator, $codes) {
            $requisition = Requisitions::query()->lockForUpdate()->find($this->requisition_id);

            if (! in_array($requisition?->status, self::RELEASABLE, true)) {
                throw ValidationException::withMessages([
                    'form.requisition_id' => 'This request has nothing left to release.',
                ]);
            }

            $lines = $requisition->items()->with('inventoryItem')->lockForUpdate()->get();

            // Plan every line first so a shortfall on one line releases nothing.
            $plans = [];
            $errors = [];

            foreach ($lines as $line) {
                $qty = (int) ($this->release[$line->id] ?? 0);
                $key = "form.release.{$line->id}";

                if ($qty === 0) {
                    continue;
                }

                if ($qty > $line->outstanding_qty) {
                    $errors[$key] = 'Only '.number_format($line->outstanding_qty).' are outstanding.';

                    continue;
                }

                $plan = $allocator->allocate($line->inventoryItem, $qty, lock: true);

                if ($plan['shortfall'] > 0) {
                    $errors[$key] = 'Only '.number_format($qty - $plan['shortfall']).' are in usable stock.';

                    continue;
                }

                $plans[] = [$line, $qty, $plan['allocations']];
            }

            if ($errors === [] && $plans === []) {
                $errors['form.release'] = 'Enter a quantity to release for at least one item.';
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $issuance = $requisition->stockIssuances()->create([
                'user_id' => Auth::id(),
                'issuance_number' => $codes->issuanceNumber(),
                'released_by' => (string) Auth::user()?->name,
                'received_by' => trim($this->received_by),
                'released_at' => now(),
                'remarks' => filled($this->remarks) ? trim($this->remarks) : null,
            ]);

            foreach ($plans as [$line, $qty, $allocations]) {
                $issuanceItem = $issuance->stockIssuanceItems()->create([
                    'requisition_item_id' => $line->id,
                    'released_qty' => $qty,
                ]);

                foreach ($allocations as ['batch' => $batch, 'quantity' => $take]) {
                    $issuanceItem->batches()->create(['batch_id' => $batch->id, 'quantity' => $take]);

                    $batch->decrement('remaining_qty', $take);

                    InventoryTransactions::create([
                        'inventory_item_id' => $line->inventory_item_id,
                        'batch_id' => $batch->id,
                        'user_id' => Auth::id(),
                        'type' => InventoryTransactionType::Issuance,
                        'quantity' => -$take,
                        'balance_after' => InventoryBatches::query()
                            ->where('inventory_item_id', $line->inventory_item_id)
                            ->sum('remaining_qty'),
                        'reference_type' => StockIssuances::class,
                        'reference_id' => $issuance->id,
                    ]);
                }

                $line->update([
                    'released_qty' => $line->released_qty + $qty,
                    'outstanding_qty' => $line->outstanding_qty - $qty,
                ]);
            }

            $requisition->update([
                'status' => $lines->sum('outstanding_qty') === 0
                    ? RequisitionStatus::Issued
                    : RequisitionStatus::PartiallyIssued,
            ]);

            return $issuance;
        });
    }
}
