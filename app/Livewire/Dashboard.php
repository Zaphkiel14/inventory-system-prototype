<?php

namespace App\Livewire;

use App\Enums\RequisitionStatus;
use App\Livewire\Forms\IssuancesForm;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\Requisitions;
use App\Services\Inventory\StockMetrics;
use App\Services\Inventory\TransactionReferences;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read int $pendingApprovals
 * @property-read int $readyToRelease
 * @property-read array{out: int, critical: int, reorder: int, healthy: int} $stockHealth
 * @property-read array{expiring: array{batches: int, units: int, value: float}, expired: array{batches: int, units: int, value: float}} $expiry
 * @property-read list<array{date: string, received: int, issued: int, written_down: int}> $movement
 */
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

    /**
     * The four "needs action" tiles. tone is a colour key the view maps to classes.
     *
     * @return list<array{label: string, value: int, note: string, icon: string, tone: string, href: string}>
     */
    #[Computed]
    public function tiles(): array
    {
        $health = $this->stockHealth;
        $expiry = $this->expiry;

        $urgent = $health['out'] + $health['critical'];
        $needsReorder = $urgent + $health['reorder'];

        return [
            [
                'label' => 'Pending approvals',
                'value' => $this->pendingApprovals,
                'note' => 'requests awaiting a decision',
                'icon' => 'clipboard-document-check',
                'tone' => 'amber',
                'href' => route('requisitions.approvals'),
            ],
            [
                'label' => 'Ready to release',
                'value' => $this->readyToRelease,
                'note' => 'approved, not fully issued',
                'icon' => 'arrow-up-tray',
                'tone' => 'sky',
                'href' => route('requisitions.issuances'),
            ],
            [
                'label' => 'Out of stock / critical',
                'value' => $urgent,
                'note' => number_format($needsReorder).' '.Str::plural('item', $needsReorder).' need reorder',
                'icon' => 'exclamation-triangle',
                'tone' => 'red',
                'href' => route('monitoring.stock-monitoring'),
            ],
            [
                'label' => 'Expiring in '.self::EXPIRY_DAYS.' days',
                'value' => $expiry['expiring']['batches'],
                'note' => number_format($expiry['expired']['batches']).' expired · '.number_format($expiry['expired']['value'], 2).' value',
                'icon' => 'clock',
                'tone' => $expiry['expired']['batches'] > 0 ? 'red' : 'amber',
                'href' => route('monitoring.expiration-monitoring'),
            ],
        ];
    }

    /**
     * Stock health breakdown. tone is a colour key the view maps to classes.
     *
     * @return list<array{label: string, value: int, tone: string}>
     */
    #[Computed]
    public function healthRows(): array
    {
        $health = $this->stockHealth;

        return [
            ['label' => 'Out of stock', 'value' => $health['out'], 'tone' => 'red'],
            ['label' => 'Critical', 'value' => $health['critical'], 'tone' => 'orange'],
            ['label' => 'Reorder', 'value' => $health['reorder'], 'tone' => 'amber'],
            ['label' => 'Healthy', 'value' => $health['healthy'], 'tone' => 'green'],
        ];
    }

    /** Active items across every stock health bucket. */
    #[Computed]
    public function activeItems(): int
    {
        return array_sum($this->stockHealth);
    }

    /* ------------------------------------------------------------------ */
    /* Lists */
    /* ------------------------------------------------------------------ */

    /**
     * Oldest requests still waiting on someone: pending approval or release.
     *
     * @return Collection<int, Requisitions>
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

    /** @return Collection<int, InventoryItems> */
    #[Computed]
    public function lowStock(): Collection
    {
        return app(StockMetrics::class)->reorderItems();
    }

    /** @return Collection<int, InventoryBatches> */
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

    /**
     * Daily received / issued / written-down units over the last MOVEMENT_DAYS days.
     *
     * @return list<array{date: string, received: int, issued: int, written_down: int}>
     */
    #[Computed]
    public function movement(): array
    {
        return app(StockMetrics::class)->dailyMovement(
            today()->subDays(self::MOVEMENT_DAYS - 1),
            today()->endOfDay(),
        );
    }

    /** Whether any day of the movement window has stock in, out or written down. */
    #[Computed]
    public function hasMovement(): bool
    {
        foreach ($this->movement as $day) {
            if ($day['received'] !== 0 || $day['issued'] !== 0 || $day['written_down'] !== 0) {
                return true;
            }
        }

        return false;
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

    /**
     * Latest ledger rows, each with a resolved 'reference' attribute (or null).
     *
     * @return Collection<int, InventoryTransactions>
     */
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

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}
