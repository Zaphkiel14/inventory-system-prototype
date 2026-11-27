<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Enums\PropertyDocumentType;
use App\Support\AuditContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * One audited change. Append-only: rows can't be updated or deleted.
 *
 * @property int $id
 * @property int|null $user_id null for changes made outside a signed-in request ("System")
 * @property AuditAction $action
 * @property string $auditable_type
 * @property string $auditable_id
 * @property array<string, mixed> $old_values
 * @property array<string, mixed> $new_values
 * @property string|null $reason
 * @property string|null $subject_type 'ics', 'par' or 'requisition', captured when logged
 * @property string|null $subject_label the record's label when logged, e.g. "ICS SPLV-2026-10-062"
 * @property CarbonImmutable $created_at
 */
class AuditLogs extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'reason',
        'subject_type',
        'subject_label',
    ];

    /** subject_type for requisitions (ICS and PAR use the document type's value). */
    public const SUBJECT_REQUISITION = 'requisition';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs are permanent and cannot be modified.'));
        static::deleting(fn () => throw new LogicException('Audit logs are permanent and cannot be deleted.'));
    }

    /**
     * Write one row for $model, by the signed-in user, with the reason given
     * or the one in AuditContext.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public static function record(AuditAction $action, Model $model, array $old = [], array $new = [], ?string $reason = null): self
    {
        return self::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => (string) $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'reason' => filled($reason) ? trim((string) $reason) : AuditContext::reason(),
            ...self::subjectOf($model),
        ]);
    }

    /**
     * The filterable type and readable label of an audited record, as it is
     * now (so the row stays findable after the record is deleted).
     *
     * @return array{subject_type: ?string, subject_label: ?string}
     */
    public static function subjectOf(Model $model): array
    {
        if ($model instanceof PropertyDocuments) {
            return ['subject_type' => $model->type->value, 'subject_label' => $model->label()];
        }

        if ($model instanceof PropertyDocumentLines) {
            $document = $model->relationLoaded('document')
                ? $model->document
                : PropertyDocuments::query()->find($model->property_document_id);

            return [
                'subject_type' => $document?->type->value,
                'subject_label' => 'Line '.($model->property_number ?: "#{$model->id}").' on '.($document?->label() ?? 'a deleted document'),
            ];
        }

        if ($model instanceof Requisitions) {
            return ['subject_type' => self::SUBJECT_REQUISITION, 'subject_label' => 'RIS '.($model->ris_number ?: $model->request_number)];
        }

        return ['subject_type' => null, 'subject_label' => null];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('auditable', 'auditable_type', 'auditable_id');
    }

    /**
     * A readable name for the audited record, e.g. "ICS SPLV-2026-10-062",
     * "PAR 2026-01-003", "RIS 26102455" or "Line LV2026-0001 on ICS …".
     * Falls back to the logged values when the record no longer exists.
     */
    public function recordLabel(): string
    {
        $record = $this->auditable;

        if ($record instanceof PropertyDocumentLines) {
            $record->loadMissing('document');
        }
        $values = $this->new_values ?: $this->old_values;

        return match ($this->auditable_type) {
            (new PropertyDocuments)->getMorphClass() => $record instanceof PropertyDocuments
                ? self::documentLabel($record)
                : self::documentLabelFromValues($values, $this->auditable_id),
            (new PropertyDocumentLines)->getMorphClass() => $record instanceof PropertyDocumentLines
                ? 'Line '.($record->property_number ?: "#{$record->id}").' on '.($record->document ? self::documentLabel($record->document) : 'a deleted document')
                : 'Line '.($values['property_number'] ?? "#{$this->auditable_id}").' (deleted)',
            (new Requisitions)->getMorphClass() => $record instanceof Requisitions
                ? 'RIS '.($record->ris_number ?: $record->request_number)
                : "RIS #{$this->auditable_id}",
            default => class_basename($this->auditable_type)." #{$this->auditable_id}",
        };
    }

    /**
     * Every field touched, as field => [old, new] (null when absent).
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function changes(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];
        $changes = [];

        foreach (array_keys($old + $new) as $field) {
            $changes[$field] = [$old[$field] ?? null, $new[$field] ?? null];
        }

        return $changes;
    }

    public static function documentLabel(PropertyDocuments $document): string
    {
        return $document->label();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private static function documentLabelFromValues(array $values, string $id): string
    {
        $type = PropertyDocumentType::tryFrom((string) ($values['type'] ?? ''));

        return ($type ? strtoupper($type->value) : 'Document').' '.($values['document_number'] ?? "#{$id}").' (deleted)';
    }
}
