<?php

namespace App\Models\Concerns;

use App\Enums\AuditAction;
use App\Models\AuditLogs;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit_logs row whenever the model is created, updated or deleted:
 * only the changed attributes (timestamps left out), the signed-in user, and
 * the reason from AuditContext. Updates inside AuditContext::as() are recorded
 * as that action (Finalized, StatusChanged) instead of Updated.
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLogs::record(AuditAction::Created, $model, [], self::auditable($model, $model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            $new = self::auditable($model, $model->getChanges());

            if ($new === []) {
                return;
            }

            $old = array_intersect_key($model->getRawOriginal(), $new);

            AuditLogs::record(AuditContext::updateAction(), $model, $old, $new);
        });

        static::deleted(function (Model $model): void {
            AuditLogs::record(AuditAction::Deleted, $model, self::auditable($model, $model->getAttributes()), []);
        });
    }

    /**
     * Attributes worth recording: everything but the key and timestamps.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function auditable(Model $model, array $attributes): array
    {
        return array_diff_key($attributes, array_flip(array_filter([
            $model->getKeyName(),
            $model->getCreatedAtColumn(),
            $model->getUpdatedAtColumn(),
        ])));
    }
}
