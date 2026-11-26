<?php

namespace App\Livewire\Monitoring;

use App\Enums\AdjustmentType;
use App\Livewire\Forms\InventoryAdjustmentsForm;
use App\Models\InventoryBatches;
use App\Models\StorageLocations;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Batches with an expiration date and stock left: how soon they expire, what
 * that stock is worth, and writing off what has already expired.
 *
 * Deferred: the page paints a skeleton first and loads the batches, bands and
 * timeline in a request fired straight after paint.
 */
#[Defer]
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

    /**
     * Per-request memo of the tracked batches inside the timeline window,
     * shared by summary() and timeline() so both read a single query.
     *
     * @var Collection<int, array{date: string, units: int, value: float}>|null
     */
    protected ?Collection $windowBatches = null;

    /**
     * Batches confirmWriteOff() already loaded, so the modal's render in the
     * same request does not query them a second time.
     *
     * @var EloquentCollection<int, InventoryBatches>|null
     */
    protected ?EloquentCollection $loadedWriteOffBatches = null;

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
     *
     * @return Builder<InventoryBatches>
     */
    protected function tracked(): Builder
    {
        return InventoryBatches::query()
            ->whereNotNull('inventory_batches.expiration_date')
            ->where('inventory_batches.remaining_qty', '>', 0);
    }

    /**
     * @param  Builder<InventoryBatches>  $query
     * @return Builder<InventoryBatches>
     */
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

    /**
     * @return LengthAwarePaginator<int, InventoryBatches>
     */
    #[Computed]
    public function batches(): LengthAwarePaginator
    {
        $query = $this->tracked()
            ->select('inventory_batches.*')
            ->with(['inventoryItem:id,name,item_code,unit', 'storageLocation:id,name'])
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
     * Batch count, units and value for each status band, keyed 'expired', 7, 30
     * and 90 (PHP stores the numeric band keys as ints).
     *
     * @return array<int|string, array{batches: int, units: int, value: float}>
     */
    #[Computed]
    public function summary(): array
    {
        $today = today()->toDateString();
        $bands = ['expired' => null, '7' => 7, '30' => 30, '90' => 90];

        return collect($bands)
            ->map(function (?int $days) use ($today): array {
                $limit = $days === null ? null : today()->addDays($days)->toDateString();

                $rows = $this->windowBatches()->filter(fn (array $batch): bool => $limit === null
                    ? $batch['date'] < $today
                    : $batch['date'] >= $today && $batch['date'] <= $limit);

                return [
                    'batches' => $rows->count(),
                    'units' => (int) $rows->sum('units'),
                    'value' => round((float) $rows->sum('value'), 2),
                ];
            })
            ->all();
    }

    /**
     * Stock value already expired, then value expiring in each of the next six months.
     *
     * @return list<array{month: string, expired: float, expiring: float}>
     */
    #[Computed]
    public function timeline(): array
    {
        $today = today()->toDateString();
        $expired = 0.0;

        /** @var array<string, array{label: string, value: float}> $months Keyed 'Y-m'. */
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $month = today()->startOfMonth()->addMonths($i);
            $months[$month->format('Y-m')] = ['label' => $month->format('M Y'), 'value' => 0.0];
        }

        foreach ($this->windowBatches() as $batch) {
            if ($batch['date'] < $today) {
                $expired += $batch['value'];

                continue;
            }

            $key = substr($batch['date'], 0, 7);
            if (isset($months[$key])) {
                $months[$key]['value'] += $batch['value'];
            }
        }

        $rows = [['month' => 'Expired', 'expired' => round($expired, 2), 'expiring' => 0.0]];
        foreach ($months as $month) {
            $rows[] = ['month' => $month['label'], 'expired' => 0.0, 'expiring' => round($month['value'], 2)];
        }

        return $rows;
    }

    /**
     * Tracked batches expiring before the end of the timeline's sixth month.
     * Every summary band (expired, 7, 30, 90 days) lies inside that window, so
     * summary() and timeline() share this one query. Plain rows, no models.
     *
     * @return Collection<int, array{date: string, units: int, value: float}>
     */
    protected function windowBatches(): Collection
    {
        return $this->windowBatches ??= $this->tracked()
            ->whereDate('inventory_batches.expiration_date', '<', today()->startOfMonth()->addMonths(6)->toDateString())
            ->toBase()
            ->get(['inventory_batches.expiration_date', 'inventory_batches.remaining_qty', 'inventory_batches.unit_cost'])
            ->map(fn (object $batch): array => [
                'date' => substr((string) $batch->expiration_date, 0, 10),
                'units' => (int) $batch->remaining_qty,
                'value' => (int) $batch->remaining_qty * (float) $batch->unit_cost,
            ]);
    }

    /**
     * @return EloquentCollection<int, StorageLocations>
     */
    #[Computed]
    public function locations(): EloquentCollection
    {
        return StorageLocations::query()
            ->whereIn('id', $this->tracked()->select('storage_location_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Expired batches the open modal will write off.
     *
     * @return EloquentCollection<int, InventoryBatches>
     */
    #[Computed]
    public function writeOffBatches(): EloquentCollection
    {
        if ($this->writeOffIds === []) {
            return new EloquentCollection;
        }

        return $this->loadedWriteOffBatches ?? $this->expiredBatches($this->writeOffIds)->get();
    }

    /**
     * The given batches that are expired and still hold stock right now.
     *
     * @param  array<int, int|string>  $ids
     * @return Builder<InventoryBatches>
     */
    protected function expiredBatches(array $ids): Builder
    {
        return InventoryBatches::query()
            ->expired()
            ->with('inventoryItem:id,name,unit')
            ->whereIn('inventory_batches.id', array_map('intval', $ids))
            ->orderBy('expiration_date')
            ->orderBy('inventory_batches.id');
    }

    /* ------------------------------------------------------------------ */
    /* Write-off */
    /* ------------------------------------------------------------------ */

    public function confirmWriteOff(?int $batchId = null): void
    {
        $ids = $batchId ? [$batchId] : $this->selected;
        $batches = $ids === [] ? new EloquentCollection : $this->expiredBatches($ids)->get();
        $this->writeOffIds = $batches->modelKeys();

        if ($this->writeOffIds === []) {
            Flux::toast(text: 'Only expired batches with stock left can be written off.', variant: 'warning');

            return;
        }

        $this->loadedWriteOffBatches = $batches;
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

        // Re-check on the server inside the transaction with the batches locked:
        // only batches that are expired *now* qualify, and each is written off
        // for exactly what it holds at that moment.
        $batches = DB::transaction(function (): EloquentCollection {
            $batches = $this->writeOffIds === []
                ? new EloquentCollection
                : $this->expiredBatches($this->writeOffIds)->lockForUpdate()->get();

            if ($batches->isEmpty()) {
                throw ValidationException::withMessages([
                    'writeOffIds' => 'None of the selected batches can be written off any more.',
                ]);
            }

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

            return $batches;
        });

        $count = $batches->count();
        $units = $batches->sum('remaining_qty');

        $this->selected = array_values(array_diff($this->selected, $batches->modelKeys()));
        $this->writeOffIds = [];
        $this->form->reset();
        $this->windowBatches = null;
        $this->loadedWriteOffBatches = null;
        unset($this->batches, $this->summary, $this->timeline, $this->locations, $this->writeOffBatches);

        Flux::modal('expiry-write-off')->close();
        Flux::toast(
            text: "Wrote off {$count} ".str('batch')->plural($count).' ('.number_format($units).' units).',
            heading: 'Expired stock written off',
            variant: 'success',
        );
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.expiration-monitoring');
    }

    public function render(): View
    {
        return view('livewire.monitoring.expiration-monitoring');
    }
}
