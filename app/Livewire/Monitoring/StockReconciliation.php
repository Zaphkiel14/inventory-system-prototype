<?php

namespace App\Livewire\Monitoring;

use App\Services\Inventory\StockReconciliation as ReconciliationService;
use App\Support\InventoryCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Defer;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only audit proving every unit of stock is accounted for.
 *
 * Deferred: the page paints a skeleton first and runs the reconciliation (a
 * full ledger walk for the exception checks) in a request fired straight after
 * paint. Rows are paged in memory on purpose: the status filter, each row's
 * exception count and totals() all need every in-scope item reconciled.
 *
 * @phpstan-import-type ReconciliationRow from ReconciliationService
 *
 * @property-read array{0: CarbonInterface, 1: CarbonInterface} $period
 * @property-read Collection<int, ReconciliationRow> $rows
 * @property-read LengthAwarePaginator<int, ReconciliationRow> $pagedRows
 * @property-read Collection<int, array<string, mixed>> $exceptions
 * @property-read LengthAwarePaginator<int, array<string, mixed>> $pagedExceptions
 * @property-read Collection<int, array<string, mixed>> $itemExceptions
 * @property-read array{items: int, balanced: int, short: int, overflow: int, unverified: int, variance_units: int} $totals
 * @property-read array<int, string> $categories
 * @property-read array<int, string> $locations
 * @property-read bool $asOfToday
 */
#[Defer]
#[Title('Stock Reconciliation')]
class StockReconciliation extends Component
{
    use WithPagination;

    public const RANGES = [
        'today' => 'Today',
        '7' => 'Last 7 days',
        '30' => 'Last 30 days',
        '90' => 'Last 90 days',
        'mtd' => 'Month to date',
        'ytd' => 'Year to date',
        'custom' => 'Custom range',
    ];

    public const STATUSES = [
        'all' => 'All items',
        'short' => 'Short',
        'overflow' => 'Overflow',
        'balanced' => 'Balanced',
        'exceptions' => 'With exceptions',
    ];

    /** Exception kinds: [full label, short badge label, plain-language explanation]. */
    public const EXCEPTION_KINDS = [
        'balance_break' => ['Running balance break', 'Balance break', 'A ledger entry\'s balance after does not equal the previous balance plus its quantity.'],
        'batch_unbalanced' => ['Batch does not add up', 'Batch', 'Received + adjusted − issued for the batch does not equal what the batch holds.'],
        'receipt_mismatch' => ['Receipt mismatch', 'Receipt', 'The batch has no Receipt entry, or its Receipt entries do not total the received quantity.'],
        'issuance_mismatch' => ['Issuance mismatch', 'Issuance', 'Issuance records drawing from the batch do not match the Issuance entries in the ledger.'],
        'negative_stock' => ['Negative stock', 'Negative', 'A ledger balance or a batch quantity dropped below zero.'],
    ];

    #[Url]
    public string $range = '30';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    #[Url]
    public ?int $category = null;

    #[Url]
    public ?int $location = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public int $perPage = 10;

    /** Paginator name and page size of the "Ledger exceptions" card. */
    public const EXCEPTIONS_PAGE_NAME = 'exceptionsPage';

    public const EXCEPTIONS_PER_PAGE = 25;

    /** Item whose exceptions the flyout shows. */
    public ?int $exceptionItem = null;

    protected ?ReconciliationService $service = null;

    public function mount(): void
    {
        $this->sanitizeFilters();
    }

    public function updated(string $property): void
    {
        if ($property === 'range') {
            if ($this->range === 'custom' && blank($this->from) && blank($this->to)) {
                $this->from = today()->subDays(29)->toDateString();
                $this->to = today()->toDateString();
            }

            if ($this->range !== 'custom') {
                $this->reset('from', 'to');
            }
        }

        $this->sanitizeFilters();
        $this->resetPage();
        $this->resetPage(self::EXCEPTIONS_PAGE_NAME);
    }

    protected function sanitizeFilters(): void
    {
        if (! array_key_exists($this->range, self::RANGES)) {
            $this->range = '30';
        }

        if (! array_key_exists($this->status, self::STATUSES)) {
            $this->status = 'all';
        }

        if ($this->category !== null && $this->category <= 0) {
            $this->category = null;
        }

        if ($this->location !== null && $this->location <= 0) {
            $this->location = null;
        }
    }

    public function clearFilters(): void
    {
        $this->reset('range', 'from', 'to', 'category', 'location', 'search', 'status');
        $this->resetPage();
        $this->resetPage(self::EXCEPTIONS_PAGE_NAME);
    }

    /* ------------------------------------------------------------------ */

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    #[Computed]
    public function period(): array
    {
        $end = today()->endOfDay();

        $start = match ($this->range) {
            'today' => today(),
            'mtd' => today()->startOfMonth(),
            'ytd' => today()->startOfYear(),
            'custom' => $this->parseDate($this->from) ?? $this->parseDate($this->to) ?? today()->subDays(29),
            default => today()->subDays((int) $this->range - 1),
        };

        if ($this->range === 'custom') {
            $end = ($this->parseDate($this->to) ?? $this->parseDate($this->from) ?? today())->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start->startOfDay(), $end];
    }

    protected function parseDate(?string $value): ?CarbonInterface
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{category: ?int, location: ?int, search: ?string, status: string}
     */
    protected function filters(): array
    {
        return [
            'category' => $this->category,
            'location' => $this->location,
            'search' => trim($this->search) !== '' ? trim($this->search) : null,
            'status' => $this->status,
        ];
    }

    protected function service(): ReconciliationService
    {
        return $this->service ??= app(ReconciliationService::class);
    }

    /** @return Collection<int, ReconciliationRow> */
    #[Computed]
    public function rows(): Collection
    {
        return $this->service()->summary($this->period, $this->filters());
    }

    /** @return LengthAwarePaginator<int, ReconciliationRow> */
    #[Computed]
    public function pagedRows(): LengthAwarePaginator
    {
        $rows = $this->rows;
        $last = max(1, (int) ceil($rows->count() / $this->perPage));
        $page = min(max(1, $this->getPage()), $last);

        if ($page !== $this->getPage()) {
            $this->setPage($page);
        }

        return new LengthAwarePaginator(
            $rows->forPage($page, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * Every exception in scope. Counts and badges read this full list; only
     * the "Ledger exceptions" card pages through it (pagedExceptions()).
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function exceptions(): Collection
    {
        return $this->service()->exceptions($this->period, $this->filters());
    }

    /**
     * One page of the "Ledger exceptions" card, on its own paginator
     * (EXCEPTIONS_PAGE_NAME) so it pages independently of the item rows.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    #[Computed]
    public function pagedExceptions(): LengthAwarePaginator
    {
        $exceptions = $this->exceptions;
        $current = $this->getPage(self::EXCEPTIONS_PAGE_NAME);
        $last = max(1, (int) ceil($exceptions->count() / self::EXCEPTIONS_PER_PAGE));
        $page = min(max(1, $current), $last);

        if ($page !== $current) {
            $this->setPage($page, self::EXCEPTIONS_PAGE_NAME);
        }

        return new LengthAwarePaginator(
            $exceptions->forPage($page, self::EXCEPTIONS_PER_PAGE)->values(),
            $exceptions->count(),
            self::EXCEPTIONS_PER_PAGE,
            $page,
            ['path' => request()->url(), 'pageName' => self::EXCEPTIONS_PAGE_NAME],
        );
    }

    public function showExceptions(int $itemId): void
    {
        $this->exceptionItem = $itemId;

        Flux::modal('item-exceptions')->show();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function itemExceptions(): Collection
    {
        if ($this->exceptionItem === null) {
            return collect();
        }

        return $this->exceptions()->where('item_id', $this->exceptionItem)->values();
    }

    /** @return array{items: int, balanced: int, short: int, overflow: int, unverified: int, variance_units: int} */
    #[Computed]
    public function totals(): array
    {
        return $this->service()->totals($this->rows);
    }

    /** @return array<int, string> */
    #[Computed]
    public function categories(): array
    {
        return InventoryCache::categories()->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    #[Computed]
    public function locations(): array
    {
        return InventoryCache::storageLocations()->pluck('name', 'id')->all();
    }

    #[Computed]
    public function asOfToday(): bool
    {
        return $this->period[1]->toDateString() >= today()->toDateString();
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.stock-reconciliation', [
            'rows' => $this->rows,
            'exceptions' => $this->exceptions,
            'totals' => $this->totals,
            'period' => $this->period,
            'filters' => $this->filters() + [
                'category_name' => $this->category ? ($this->categories[$this->category] ?? null) : null,
                'location_name' => $this->location ? ($this->locations[$this->location] ?? null) : null,
            ],
            'generatedBy' => auth()->user()->name,
            'generatedAt' => now(),
            'asOfToday' => $this->asOfToday,
        ])->setPaper('a4', 'landscape');

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 90,
            $canvas->get_height() - 28,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $dompdf->getFontMetrics()->getFont('DejaVu Sans'),
            8,
            [0.42, 0.45, 0.5],
        );

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'stock-reconciliation-'.now()->format('Ymd-His').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Skeleton shown until the deferred load returns.
     *
     * @param  array<string, mixed>  $params
     */
    public function placeholder(array $params = []): View
    {
        return view('livewire.monitoring.placeholders.stock-reconciliation');
    }

    public function render(): View
    {
        return view('livewire.monitoring.stock-reconciliation');
    }
}
