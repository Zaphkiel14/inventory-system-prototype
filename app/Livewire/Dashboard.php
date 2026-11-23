<?php

namespace App\Livewire;

use App\Enums\RequisitionStatus;
use App\Livewire\Forms\IssuancesForm;
use App\Models\InventoryBatches;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Services\Inventory\StockMetrics;
use App\Services\Inventory\TransactionReferences;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    /** Rows shown in each list widget. */
    public const LIST_LIMIT = 6;

    public const ACTIVITY_LIMIT = 8;

    /** Days covered by the movement chart and the "expiring soon" window. */
    public const MOVEMENT_DAYS = 30;

    public const EXPIRY_DAYS = 30;

    /* ------------------------------------------------------------------ */
    /* "Needs action" tiles */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function pendingApprovals(): int
    {
        return Requisitions::query()->where('status', RequisitionStatus::Pending)->count();
    }

    #[Computed]
    public function readyToRelease(): int
    {
        return Requisitions::query()->whereIn('status', IssuancesForm::RELEASABLE)->count();
    }

    /**
     * @return array{out: int, critical: int, reorder: int, healthy: int}
     */
    #[Computed]
    public function stockHealth(): array
    {
        return app(StockMetrics::class)->stockHealth();
    }

    /**
     * Batches expiring soon and already expired: count, units and value.
     *
     * @return array{expiring: array{batches: int, units: int, value: float}, expired: array{batches: int, units: int, value: float}}
     */
    #[Computed]
    public function expiry(): array
    {
        $totals = function ($query): array {
            $row = $query
                ->selectRaw('count(*) as batches, coalesce(sum(remaining_qty), 0) as units, coalesce(sum(remaining_qty * unit_cost), 0) as value')
                ->toBase()
                ->first();

            return ['batches' => (int) $row->batches, 'units' => (int) $row->units, 'value' => (float) $row->value];
        };

        return [
            'expiring' => $totals(InventoryBatches::query()->expiringWithin(self::EXPIRY_DAYS)),
            'expired' => $totals(InventoryBatches::query()->expired()),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Lists */
    /* ------------------------------------------------------------------ */

    /**
     * Oldest requests still waiting on someone: pending approval or release.
     */
    #[Computed]
    public function queue(): Collection
    {
        return Requisitions::query()
            ->with('requester:id,name')
            ->withCount('items')
            ->whereIn('status', [RequisitionStatus::Pending, ...IssuancesForm::RELEASABLE])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->limit(self::LIST_LIMIT)
            ->get();
    }

    #[Computed]
    public function queueTotal(): int
    {
        return $this->pendingApprovals + $this->readyToRelease;
    }

    #[Computed]
    public function lowStock(): Collection
    {
        return app(StockMetrics::class)->reorderItems();
    }

    #[Computed]
    public function expiringSoon(): Collection
    {
        return InventoryBatches::query()
            ->expiringWithin(self::EXPIRY_DAYS)
            ->with('inventoryItem:id,name,unit')
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->limit(self::LIST_LIMIT)
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /* Movement + activity */
    /* ------------------------------------------------------------------ */

    #[Computed]
    public function movement(): array
    {
        return app(StockMetrics::class)->dailyMovement(
            today()->subDays(self::MOVEMENT_DAYS - 1),
            today()->endOfDay(),
        );
    }

    /**
     * Usable stock on hand: units and value.
     *
     * @return array{units: int, value: float}
     */
    #[Computed]
    public function onHand(): array
    {
        $row = InventoryBatches::query()->usable()
            ->select(DB::raw('coalesce(sum(remaining_qty), 0) as units, coalesce(sum(remaining_qty * unit_cost), 0) as value'))
            ->toBase()
            ->first();

        return ['units' => (int) $row->units, 'value' => (float) $row->value];
    }

    #[Computed]
    public function recentActivity(): Collection
    {
        $transactions = InventoryTransactions::query()
            ->with(['inventoryItem:id,name,unit', 'user:id,name'])
            ->latest('created_at')
            ->latest('id')
            ->limit(self::ACTIVITY_LIMIT)
            ->get();

        $references = app(TransactionReferences::class)->resolve($transactions);

        return $transactions->each(fn (InventoryTransactions $t) => $t->setAttribute(
            'reference', $references[$t->reference_type.':'.$t->reference_id] ?? null,
        ));
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
