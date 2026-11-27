<?php

namespace App\Livewire\Forms;

use App\Enums\InventoryTransactionType;
use App\Enums\RequisitionStatus;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\IssuedPropertyUnits;
use App\Models\RequisitionItems;
use App\Models\Requisitions;
use App\Models\StockIssuances;
use App\Services\Inventory\BatchAllocator;
use App\Services\Inventory\BatchCodeGenerator;
use App\Services\Property\PropertyClassifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Releases approved quantities. This is the only requisition stage that moves
 * stock: each released line is drawn from batches (FEFO for items with an
 * expiry date, FIFO otherwise) and logged as an inventory transaction.
 * A requisition can be released over several issuances until nothing is
 * outstanding.
 *
 * Property items (is_property) also get one issued_property_units row per
 * released unit, carrying the brand, model and serial typed for it and the
 * cost of the batch it was drawn from.
 */
class IssuancesForm extends Form
{
    /** Requisition statuses that still have approved stock to release. */
    public const RELEASABLE = [RequisitionStatus::Approved, RequisitionStatus::PartiallyIssued];

    /** Set by load() only. */
    #[Locked]
    public ?int $requisition_id = null;

    /** @var array<int|string, mixed> requisition_item_id => quantity to release now */
    public array $release = [];

    /** @var array<int|string, mixed> requisition_item_id => remarks for the RIS issue column */
    public array $issue_remarks = [];

    /**
     * One row per property unit in the planned release, built by planUnits().
     * batch_id, unit_cost and property_class are display-only: store() takes
     * them from the locked allocation, never from this array.
     *
     * @var array<int|string, array<int, array{batch_id: int, unit_cost: string, property_class: string, brand: ?string, model: ?string, serial_number: ?string}>>
     */
    public array $units = [];

    public string $received_by = '';

    public string $released_by_designation = '';

    public string $received_by_designation = '';

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
        $this->received_by_designation = (string) $requisition->requester_position;

        $onHand = InventoryItems::query()
            ->whereKey($requisition->items->pluck('inventory_item_id'))
            ->withAvailableQty()
            ->pluck('available_qty', 'id');

        foreach ($requisition->items as $line) {
            $this->issue_remarks[$line->id] = (string) $line->issue_remarks;

            if ($line->outstanding_qty > 0) {
                $this->release[$line->id] = min($line->outstanding_qty, (int) ($onHand[$line->inventory_item_id] ?? 0));
            }
        }
    }

    /**
     * Rebuild the per-unit rows from a batch preview (Issuances::preview(),
     * the same plan store() draws). Typed brand/model/serial values are kept
     * by position, so changing a quantity doesn't wipe what was entered.
     *
     * @param  array<int, array{allocations: Collection<int, array{batch: InventoryBatches, quantity: int}>, shortfall: int}>  $preview
     */
    public function planUnits(Requisitions $requisition, array $preview): void
    {
        $classifier = app(PropertyClassifier::class);
        $units = [];

        foreach ($requisition->items as $line) {
            $item = $line->inventoryItem;

            if (! $item?->is_property || ! isset($preview[$line->id])) {
                continue;
            }

            $previous = $this->units[$line->id] ?? [];
            $rows = [];

            foreach ($preview[$line->id]['allocations'] as ['batch' => $batch, 'quantity' => $quantity]) {
                $class = $classifier->classify($item, (string) $batch->unit_cost)->value;

                for ($i = 0; $i < $quantity; $i++) {
                    $typed = $previous[count($rows)] ?? [];

                    $rows[] = [
                        'batch_id' => $batch->id,
                        'unit_cost' => number_format((float) $batch->unit_cost, 2, '.', ''),
                        'property_class' => $class,
                        'brand' => $typed['brand'] ?? null,
                        'model' => $typed['model'] ?? null,
                        'serial_number' => $typed['serial_number'] ?? null,
                    ];
                }
            }

            $units[$line->id] = $rows;
        }

        $this->units = $units;
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
            'released_by_designation' => ['nullable', 'string', 'max:255'],
            'received_by_designation' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'issue_remarks' => ['array'],
            'issue_remarks.*' => ['nullable', 'string', 'max:255'],
            'units' => ['array'],
            'units.*' => ['array'],
            'units.*.*.brand' => ['nullable', 'string', 'max:255'],
            'units.*.*.model' => ['nullable', 'string', 'max:255'],
            'units.*.*.serial_number' => ['nullable', 'string', 'max:255'],
        ], attributes: [
            'release.*' => 'release quantity',
            'received_by' => 'received by',
            'released_by_designation' => 'released by (designation)',
            'received_by_designation' => 'received by (designation)',
            'issue_remarks.*' => 'remarks',
            'units.*.*.brand' => 'brand',
            'units.*.*.model' => 'model',
            'units.*.*.serial_number' => 'serial number',
        ]);

        $this->validateSerials();
    }

    /**
     * A serial number is unique per item: not repeated within this release and
     * not already on an issued unit of the same item.
     */
    protected function validateSerials(): void
    {
        $itemIds = RequisitionItems::query()
            ->where('requisition_id', $this->requisition_id)
            ->whereKey(array_keys($this->units))
            ->pluck('inventory_item_id', 'id');

        $entered = [];

        foreach ($this->units as $lineId => $rows) {
            foreach ($rows as $i => $row) {
                $serial = $this->clean($row['serial_number'] ?? null);

                if ($serial !== null && isset($itemIds[$lineId])) {
                    $entered[] = ['key' => "form.units.{$lineId}.{$i}.serial_number", 'item' => (int) $itemIds[$lineId], 'serial' => $serial];
                }
            }
        }

        if ($entered === []) {
            return;
        }

        $taken = IssuedPropertyUnits::query()
            ->whereIn('inventory_item_id', array_unique(array_column($entered, 'item')))
            ->whereIn('serial_number_normalized', array_unique(array_map(
                fn (string $serial) => IssuedPropertyUnits::normalizeSerial($serial),
                array_column($entered, 'serial'),
            )))
            ->get(['inventory_item_id', 'serial_number_normalized'])
            ->map(fn (IssuedPropertyUnits $unit) => $unit->inventory_item_id.'|'.$unit->serial_number_normalized)
            ->flip();

        $errors = [];
        $seen = [];

        foreach ($entered as ['key' => $key, 'item' => $item, 'serial' => $serial]) {
            $id = $item.'|'.IssuedPropertyUnits::normalizeSerial($serial);

            if ($taken->has($id)) {
                $errors[$key] = "Serial number {$serial} was already issued for this item.";
            } elseif (isset($seen[$id])) {
                $errors[$key] = "Serial number {$serial} is entered more than once.";
            }

            $seen[$id] = true;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function store(): StockIssuances
    {
        $this->validateInput();

        $allocator = app(BatchAllocator::class);
        $codes = app(BatchCodeGenerator::class);
        $classifier = app(PropertyClassifier::class);

        try {
            return $this->release($allocator, $codes, $classifier);
        } catch (UniqueConstraintViolationException $e) {
            // Another release saved the same serial between our check and insert.
            if (! str_contains($e->getMessage(), 'serial_number_normalized')) {
                throw $e;
            }

            throw ValidationException::withMessages([
                'form.units' => 'One of these serial numbers was just issued in another release. Review the serial numbers and release again.',
            ]);
        }
    }

    /**
     * The release itself, all-or-nothing in one transaction.
     */
    protected function release(BatchAllocator $allocator, BatchCodeGenerator $codes, PropertyClassifier $classifier): StockIssuances
    {
        return DB::transaction(function () use ($allocator, $codes, $classifier) {
            $requisition = Requisitions::query()->lockForUpdate()->find($this->requisition_id);

            if (! in_array($requisition?->status, self::RELEASABLE, true)) {
                throw ValidationException::withMessages([
                    'form.requisition_id' => 'This request has nothing left to release.',
                ]);
            }

            $lines = $requisition->items()->with('inventoryItem')->orderBy('id')->lockForUpdate()->get();

            // Plan every line first so a shortfall on one line releases nothing.
            // Lines are planned cumulatively, in line order: $taken carries each
            // batch's units already planned by earlier lines, so two lines for
            // the same item never draw the same units and their combined
            // quantity is checked against usable stock.
            $plans = [];
            $errors = [];
            $taken = [];

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

                $plan = $allocator->allocate($line->inventoryItem, $qty, lock: true, taken: $taken);

                if ($plan['shortfall'] > 0) {
                    $errors[$key] = 'Only '.number_format($qty - $plan['shortfall']).' are in usable stock.';

                    continue;
                }

                if ($line->inventoryItem->is_property && ! $this->unitsMatch($line->id, $plan['allocations'])) {
                    $errors["form.units.{$line->id}"] = 'Stock changed since this was opened, so the units no longer match the batches. Review the units and release again.';

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
                'released_by_designation' => $this->clean($this->released_by_designation),
                'received_by' => trim($this->received_by),
                'received_by_designation' => $this->clean($this->received_by_designation),
                'released_at' => now(),
                'remarks' => filled($this->remarks) ? trim($this->remarks) : null,
            ]);

            $released = [];

            foreach ($plans as [$line, $qty, $allocations]) {
                $issuanceItem = $issuance->stockIssuanceItems()->create([
                    'requisition_item_id' => $line->id,
                    'released_qty' => $qty,
                ]);

                // Item total read once per line (after earlier lines' draws), then
                // reduced per batch: the batches are locked, so it can't drift.
                $balance = (int) InventoryBatches::query()
                    ->where('inventory_item_id', $line->inventory_item_id)
                    ->sum('remaining_qty');

                $unitRows = array_values($this->units[$line->id] ?? []);
                $unitIndex = 0;

                foreach ($allocations as ['batch' => $batch, 'quantity' => $take]) {
                    $draw = $issuanceItem->batches()->create(['batch_id' => $batch->id, 'quantity' => $take]);

                    if ($line->inventoryItem->is_property) {
                        $class = $classifier->classify($line->inventoryItem, (string) $batch->unit_cost);

                        for ($i = 0; $i < $take; $i++, $unitIndex++) {
                            $draw->issuedPropertyUnits()->create([
                                'inventory_item_id' => $line->inventory_item_id,
                                'brand' => $this->clean($unitRows[$unitIndex]['brand'] ?? null),
                                'model' => $this->clean($unitRows[$unitIndex]['model'] ?? null),
                                'serial_number' => $this->clean($unitRows[$unitIndex]['serial_number'] ?? null),
                                'unit_cost' => $batch->unit_cost,
                                'property_class' => $class,
                            ]);
                        }
                    }

                    $batch->decrement('remaining_qty', $take);
                    $balance -= $take;

                    InventoryTransactions::create([
                        'inventory_item_id' => $line->inventory_item_id,
                        'batch_id' => $batch->id,
                        'user_id' => Auth::id(),
                        'type' => InventoryTransactionType::Issuance,
                        'quantity' => -$take,
                        'balance_after' => $balance,
                        'reference_type' => StockIssuances::class,
                        'reference_id' => $issuance->id,
                    ]);
                }

                $line->update([
                    'released_qty' => $line->released_qty + $qty,
                    'outstanding_qty' => $line->outstanding_qty - $qty,
                    'issue_remarks' => $this->remarksFor($line),
                ]);

                $released[$line->id] = true;
            }

            // Remarks typed on lines that weren't released this time.
            foreach ($lines as $line) {
                if (! isset($released[$line->id]) && $line->issue_remarks !== $this->remarksFor($line)) {
                    $line->update(['issue_remarks' => $this->remarksFor($line)]);
                }
            }

            $requisition->update([
                'status' => $lines->sum('outstanding_qty') === 0
                    ? RequisitionStatus::Issued
                    : RequisitionStatus::PartiallyIssued,
            ]);

            return $issuance;
        });
    }

    /**
     * Whether the unit rows shown were planned from exactly this allocation:
     * the same batches, in the same order, one row per unit.
     *
     * @param  Collection<int, array{batch: InventoryBatches, quantity: int}>  $allocations
     */
    protected function unitsMatch(int $lineId, Collection $allocations): bool
    {
        $expected = $allocations
            ->flatMap(fn (array $allocation) => array_fill(0, $allocation['quantity'], $allocation['batch']->id))
            ->all();

        $planned = array_map(fn (array $row) => (int) $row['batch_id'], array_values($this->units[$lineId] ?? []));

        return $planned === $expected;
    }

    protected function remarksFor(RequisitionItems $line): ?string
    {
        return array_key_exists($line->id, $this->issue_remarks)
            ? $this->clean($this->issue_remarks[$line->id])
            : $line->issue_remarks;
    }

    protected function clean(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
