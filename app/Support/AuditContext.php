<?php

namespace App\Support;

use App\Enums\AuditAction;
use Closure;

/**
 * The reason (and, for finalize/status changes, the action) that Auditable
 * models record for writes made inside a callback. Scoped: the previous
 * context is restored afterwards, even when the callback throws.
 */
final class AuditContext
{
    private static ?string $reason = null;

    private static ?AuditAction $action = null;

    /**
     * Run $callback with $reason attached to every audit row it writes.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function withReason(?string $reason, Closure $callback): mixed
    {
        return self::as(null, $reason, $callback);
    }

    /**
     * Run $callback with updates recorded as $action (e.g. Finalized or
     * StatusChanged) instead of Updated, and with $reason attached.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function as(?AuditAction $action, ?string $reason, Closure $callback): mixed
    {
        [$previousAction, $previousReason] = [self::$action, self::$reason];
        self::$action = $action ?? $previousAction;
        self::$reason = filled($reason) ? trim((string) $reason) : $previousReason;

        try {
            return $callback();
        } finally {
            [self::$action, self::$reason] = [$previousAction, $previousReason];
        }
    }

    public static function reason(): ?string
    {
        return self::$reason;
    }

    /**
     * The action an update is recorded as (Updated unless overridden).
     */
    public static function updateAction(): AuditAction
    {
        return self::$action ?? AuditAction::Updated;
    }
}
