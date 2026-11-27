<?php

namespace App\Livewire\Monitoring;

use App\Enums\AuditAction;
use App\Enums\PropertyDocumentType;
use App\Models\AuditLogs as AuditLog;
use App\Models\PropertyDocumentLines;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The audit trail: who changed what, when and why, with old/new values.
 * Lines are listed under their document's type (an ICS line counts as ICS).
 *
 * @property-read LengthAwarePaginator<int, AuditLog> $logs
 * @property-read array<int, string> $users
 * @property-read array<string, string> $typeOptions
 * @property-read array<string, string> $actionOptions
 */
class AuditLogs extends Component
{
    use WithPagination;

    /** Matches user name, record label (document/property/RIS numbers) and reason. */
    public string $search = '';

    /** '' or an AuditAction value. */
    public string $action = '';

    /** '', 'ics', 'par' or 'requisition'. */
    public string $auditableType = '';

    /** Y-m-d */
    public string $from = '';

    /** Y-m-d */
    public string $to = '';

    /** '' or a user id. */
    public string $user = '';

    public int $perPage = 15;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'action', 'auditableType', 'from', 'to', 'user', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, AuditLog>
     */
    #[Computed]
    public function logs(): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with([
                'user:id,name',
                'auditable' => fn (MorphTo $morph) => $morph->morphWith([
                    PropertyDocumentLines::class => ['document'],
                ]),
            ])
            ->when(AuditAction::tryFrom($this->action), fn (Builder $query, AuditAction $action) => $query->where('action', $action))
            ->when($this->user !== '', fn (Builder $query) => $query->where('user_id', (int) $this->user))
            ->when($this->validDate($this->from), fn (Builder $query) => $query->whereDate('created_at', '>=', $this->from))
            ->when($this->validDate($this->to), fn (Builder $query) => $query->whereDate('created_at', '<=', $this->to))
            // subject_type/subject_label are captured when the row is written, so
            // rows of deleted drafts and lines still match.
            ->when(in_array($this->auditableType, array_keys($this->typeOptions), true),
                fn (Builder $query) => $query->where('subject_type', $this->auditableType))
            ->when(trim($this->search) !== '', function (Builder $query) {
                $term = '%'.trim($this->search).'%';

                $query->where(fn (Builder $query) => $query
                    ->where('reason', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhereIn('user_id', User::query()->select('id')->where('name', 'like', $term)));
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate($this->perPage);
    }

    /**
     * Users who appear in the log (id => name).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function users(): array
    {
        return User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Record type filter choices (key => label).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return [
            PropertyDocumentType::Ics->value => 'ICS',
            PropertyDocumentType::Par->value => 'PAR',
            AuditLog::SUBJECT_REQUISITION => 'RIS',
        ];
    }

    /**
     * Action filter choices (value => label).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function actionOptions(): array
    {
        return collect(AuditAction::cases())
            ->mapWithKeys(fn (AuditAction $action) => [$action->value => $action->label()])
            ->all();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'action', 'auditableType', 'from', 'to', 'user');
        $this->resetPage();
    }

    private function validDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    public function render(): View
    {
        return view('livewire.monitoring.audit-logs');
    }
}
