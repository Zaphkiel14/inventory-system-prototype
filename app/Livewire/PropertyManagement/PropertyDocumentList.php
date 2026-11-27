<?php

namespace App\Livewire\PropertyManagement;

use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Models\PropertyDocuments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Shared list for ICS and PAR documents: search, status, renewal-due and
 * source-issuance filters, sorting and pagination. Line counts and totals
 * come from aggregate subqueries, so the page is one list query plus the
 * paginator count and the eager-loaded requisitions.
 *
 * Each row carries lines_count and lines_total (2dp string).
 *
 * @property-read LengthAwarePaginator<int, PropertyDocuments> $documents
 * @property-read array<string, string> $statusOptions
 */
abstract class PropertyDocumentList extends Component
{
    use WithPagination;

    /** Columns the list can be sorted by. */
    public const SORTABLE = ['document_date', 'document_number', 'to_name', 'renewal_due_on', 'status'];

    /** Days ahead the "renewal due" filter looks. */
    public const RENEWAL_WINDOW_DAYS = 30;

    public string $search = '';

    /** '' for every status, or a PropertyDocumentStatus value. */
    public string $status = '';

    /** Active documents whose renewal falls within RENEWAL_WINDOW_DAYS (or is overdue). */
    public bool $renewalDue = false;

    /** Only documents drafted from this stock issuance (set by the post-release redirect). */
    #[Url]
    public ?int $issuance = null;

    public string $sortBy = 'document_date';

    public string $sortDirection = 'desc';

    public int $perPage = 10;

    abstract public function documentType(): PropertyDocumentType;

    /**
     * @return view-string
     */
    abstract protected function viewName(): string;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'renewalDue', 'issuance', 'perPage'], true)) {
            if ($property === 'status' && $this->status !== '' && PropertyDocumentStatus::tryFrom($this->status) === null) {
                $this->status = '';
            }

            $this->resetPage();
        }
    }

    public function sort(string $column): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearIssuance(): void
    {
        $this->issuance = null;
        $this->resetPage();
    }

    #[On('property-document-saved')]
    public function refreshDocuments(): void
    {
        unset($this->documents);
    }

    /**
     * @return LengthAwarePaginator<int, PropertyDocuments>
     */
    #[Computed]
    public function documents(): LengthAwarePaginator
    {
        $sortBy = in_array($this->sortBy, self::SORTABLE, true) ? $this->sortBy : 'document_date';
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $page = PropertyDocuments::query()
            ->where('type', $this->documentType())
            ->with('requisition:id,ris_number,request_number,office')
            ->withCount('lines')
            ->withSum('lines as lines_total_raw', DB::raw('quantity * unit_cost'))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->renewalDue, fn (Builder $query) => $query
                ->where('status', PropertyDocumentStatus::Active)
                ->whereDate('renewal_due_on', '<=', today()->addDays(self::RENEWAL_WINDOW_DAYS)))
            ->when($this->issuance, fn (Builder $query, int $issuance) => $query->where('stock_issuance_id', $issuance))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $term = '%'.trim($this->search).'%';

                $query->where(fn (Builder $query) => $query
                    ->where('document_number', 'like', $term)
                    ->orWhere('to_name', 'like', $term)
                    ->orWhere('fund_cluster', 'like', $term)
                    ->orWhereHas('lines', fn (Builder $q) => $q
                        ->where('property_number', 'like', $term)
                        ->orWhere('serial_number', 'like', $term)
                        ->orWhere('item_name', 'like', $term))
                    ->orWhereHas('requisition', fn (Builder $q) => $q
                        ->where('office', 'like', $term)
                        ->orWhere('ris_number', 'like', $term)));
            })
            ->orderBy($sortBy, $direction)
            ->orderBy('id', $direction)
            ->paginate($this->perPage);

        $page->getCollection()->each(function (PropertyDocuments $document): void {
            $document->setAttribute('lines_total', number_format((float) ($document->getAttribute('lines_total_raw') ?? 0), 2, '.', ''));
        });

        return $page;
    }

    /**
     * Status filter choices (value => label).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(PropertyDocumentStatus::cases())
            ->mapWithKeys(fn (PropertyDocumentStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public function render(): View
    {
        return view($this->viewName());
    }
}
