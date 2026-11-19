<?php

namespace App\Livewire\Monitoring;

use App\Enums\AdjustmentType;
use App\Livewire\Forms\InventoryAdjustmentsForm;
use App\Models\InventoryBatches;
use App\Models\StorageLocations;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batches with an expiration date and stock left: how soon they expire, what
 * that stock is worth, and writing off what has already expired.
 */
class ExpirationMonitoring extends Component
{
    use WithPagination;

    /**
     * Status filter => label. The numbers are "expires within N days".
     */
    public const STATUSES = [
        '' => 'All tracked batches',
        'expired' => 'Expired',
        '7' => 'Expires within 7 days',
        '30' => 'Expires within 30 days',
        '90' => 'Expires within 90 days',
        'later' => 'Expires after 90 days',
    ];

    public const SORTS = [
        'soonest' => 'Expires soonest',
        'latest' => 'Expires latest',
        'value' => 'Highest value',
        'quantity' => 'Most units',
    ];

    public const DEFAULT_REASON = 'Batch passed its expiration date and was disposed of per policy.';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $location = '';

    #[Url(except: 'soonest')]
    public string $sort = 'soonest';

    public int $perPage = 15;

    /** @var array<int, int> Expired batch ids ticked for a bulk write-off. */
    public array $selected = [];

    /** @var array<int, int> Batch ids the open write-off modal applies to. */
    public array $writeOffIds = [];

    public string $reason = self::DEFAULT_REASON;

    public string $requestedBy = '';

    public string $approvedBy = '';

    public InventoryAdjustmentsForm $form;

    public function mount(): void
    {
        $this->sanitizeFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'location', 'sort'], true)) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    protected function sanitizeFilters(): void
    {
        if (! array_key_exists($this->status, self::STATUSES)) {
            $this->status = '';
        }

        if (! array_key_exists($this->sort, self::SORTS)) {
            $this->sort = 'soonest';
        }

        if ($this->location !== '' && ! ctype_digit($this->location)) {
            $this->location = '';
        }
    }

    public function filterStatus(string $status): void
    {
        $this->status = $this->status === $status ? '' : $status;
        $this->sanitizeFilters();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'status', 'location', 'sort');
        $this->resetPage();
    }

    /* ------------------------------------------------------------------ */
    /* Queries */
    /* ------------------------------------------------------------------ */

    /**
     * Batches that can still expire on someone: dated and not empty.
     */
    protected function tracked(): Builder
    {
        return InventoryBatches::query()
            ->whereNotNull('inventory_batches.expiration_date')
            ->where('inventory_batches.remaining_qty', '>', 0);
    }

    protected function applyStatus(Builder $query, string $status): Builder
    {
        $column = 'inventory_batches.expiration_date';
        $today = today()->toDateString();

        return match ($status) {
            'expired' => $query->whereDate($column, '<', $today),
            '7', '30', '90' => $query->whereDate($column, '>=', $today)
                ->whereDate($column, '<=', today()->addDays((int) $status)->toDateString()),
            'later' => $query->whereDate($column, '>', today()->addDays(90)->toDateString()),
            default => $query,
        };
    }

    #[Computed]
    public function batches()
    {
        $query = $this->tracked()
            ->select('inventory_batches.*')
            ->with(['inventoryItem', 'storageLocation', 'stockReceipt.supplier'])
            ->when($this->search !== '', function (Builder $query) {
                $term = "%{$this->search}%";

                $query->where(fn (Builder $q) => $q
                    ->where('inventory_batches.batch_number', 'like', $term)
                    ->orWhere('inventory_batches.lot_number', 'like', $term)
                    ->orWhereHas('inventoryItem', fn (Builder $i) => $i
                        ->where('name', 'like', $term)
                        ->orWhere('item_code', 'like', $term)));
            })
            ->when($this->location !== '', fn (Builder $q) => $q->where('inventory_batches.storage_location_id', (int) $this->location));

        $this->applyStatus($query, $this->status);

        match ($this->sort) {
            'latest' => $query->orderByDesc('inventory_batches.expiration_date'),
            'value' => $query->orderByRaw('inventory_batches.remaining_qty * inventory_batches.unit_cost desc'),
            'quantity' => $query->orderByDesc('inventory_batches.remaining_qty'),
            default => $query->orderBy('inventory_batches.expiration_date'),
        };

        return $query->orderBy('inventory_batches.id')->paginate($this->perPage);
    }

    /**
     * Batch count, units and value for each status band.
     *
     * @return array<string, array{batches: int, units: int, value: float}>
     */
    #[Computed]
    public function summary(): array
    {
        return collect(['expired', '7', '30', '90'])
            ->mapWithKeys(function (string $status) {
                $row = $this->applyStatus($this->tracked(), $status)
                    ->selectRaw('count(*) as batches, coalesce(sum(remaining_qty), 0) as units, coalesce(sum(remaining_qty * unit_cost), 0) as value')
                    ->first();

                return [$status => [
                    'batches' => (int) $row->batches,
                    'units' => (int) $row->units,
                    'value' => (float) $row->value,
                ]];
            })
            ->all();
    }

    /**
     * Stock value already expired, then value expiring in each of the next six months.
     */
    #[Computed]
    public function timeline(): array
    {
        $rows = [['month' => 'Expired', 'expired' => 0.0, 'expiring' => 0.0]];
        for ($i = 0; $i < 6; $i++) {
            $month = today()->startOfMonth()->addMonths($i);
            $rows[$month->format('Y-m')] = ['month' => $month->format('M Y'), 'expired' => 0.0, 'expiring' => 0.0];
        }

        $this->tracked()
            ->whereDate('expiration_date', '<', today()->startOfMonth()->addMonths(6)->toDateString())
            ->get(['expiration_date', 'remaining_qty', 'unit_cost'])
            ->each(function (InventoryBatches $batch) use (&$rows) {
                $value = $batch->remaining_qty * (float) $batch->unit_cost;

                if ($batch->expiration_date->lt(today())) {
                    $rows[0]['expired'] += $value;

                    return;
                }

                $key = $batch->expiration_date->format('Y-m');
                if (isset($rows[$key])) {
                    $rows[$key]['expiring'] += $value;
                }
            });

        return array_map(fn ($row) => [
            ...$row,
            'expired' => round($row['expired'], 2),
            'expiring' => round($row['expiring'], 2),
        ], array_values($rows));
    }

    #[Computed]
    public function locations(): Collection
    {
        return StorageLocations::query()
            ->whereIn('id', $this->tracked()->select('storage_location_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Expired batches the open modal will write off.
     */
    #[Computed]
    public function writeOffBatches(): Collection
    {
        return $this->expiredBatches($this->writeOffIds)->get();
    }

    protected function expiredBatches(array $ids): Builder
    {
        return $this->applyStatus($this->tracked(), 'expired')
            ->with('inventoryItem')
            ->whereIn('inventory_batches.id', array_map('intval', $ids))
            ->orderBy('expiration_date');
    }

    /* ------------------------------------------------------------------ */
    /* Write-off */
    /* ------------------------------------------------------------------ */

    public function confirmWriteOff(?int $batchId = null): void
    {
        $ids = $batchId ? [$batchId] : $this->selected;
        $this->writeOffIds = $this->expiredBatches($ids)->pluck('inventory_batches.id')->all();

        if ($this->writeOffIds === []) {
            Flux::toast(text: 'Only expired batches with stock left can be written off.', variant: 'warning');

            return;
        }

        unset($this->writeOffBatches);
        $this->resetValidation();
        $this->reason = self::DEFAULT_REASON;
        $this->requestedBy = (string) Auth::user()?->name;
        $this->approvedBy = '';

        Flux::modal('expiry-write-off')->show();
    }

    public function writeOff(): void
    {
        $this->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'requestedBy' => ['required', 'string', 'max:255'],
            'approvedBy' => ['required', 'string', 'max:255'],
        ], attributes: ['requestedBy' => 'requested by', 'approvedBy' => 'approved by']);

        // Re-check on the server: only batches that are expired *now* qualify.
        $batches = $this->expiredBatches($this->writeOffIds)->get();

        if ($batches->isEmpty()) {
            throw ValidationException::withMessages([
                'writeOffIds' => 'None of the selected batches can be written off any more.',
            ]);
        }

        DB::transaction(function () use ($batches) {
            foreach ($batches as $batch) {
                $this->form->fill([
                    'batch_id' => $batch->id,
                    'type' => AdjustmentType::ExpiryWriteOff->value,
                    'quantity' => $batch->remaining_qty,
                    'reason' => $this->reason,
                    'requested_by' => $this->requestedBy,
                    'approved_by' => $this->approvedBy,
                ]);

                $this->form->store();
            }
        });

        $count = $batches->count();
        $units = $batches->sum('remaining_qty');

        $this->selected = array_values(array_diff($this->selected, $batches->pluck('id')->all()));
        $this->writeOffIds = [];
        $this->form->reset();
        unset($this->batches, $this->summary, $this->timeline, $this->locations, $this->writeOffBatches);

        Flux::modal('expiry-write-off')->close();
        Flux::toast(
            text: "Wrote off {$count} ".str('batch')->plural($count).' ('.number_format($units).' units).',
            heading: 'Expired stock written off',
            variant: 'success',
        );
    }

    public function render()
    {
        return view('livewire.monitoring.expiration-monitoring');
    }
}
