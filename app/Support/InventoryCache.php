<?php

namespace App\Support;

use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cache keys for inventory figures and lookup lists, in one place.
 *
 * Stock figures are tagged with a ledger version: every stock movement,
 * batch change or item change sets a new version (after its transaction
 * commits), and a cached entry whose version doesn't match is rebuilt, so a
 * figure is never served after the data it was built from changed. Keys are
 * stable (the version is stored in the value, not the key), so a bump
 * overwrites entries instead of stranding old files in the file store.
 *
 * config/cache.php refuses to unserialize objects, so cached values must be
 * scalars or arrays; models are cached as raw attributes and rehydrated.
 */
final class InventoryCache
{
    public const LEDGER_VERSION = 'inventory:ledger-version';

    /** Safety-net lifetime for figures tagged with the ledger version, in seconds. */
    public const LEDGER_TTL = 300;

    /** Lifetime of lookup lists, in seconds (busted by their model events). */
    public const LOOKUP_TTL = 3600;

    /* Lookup lists. Each is forgotten by its model's saved/deleted hook. */

    public const LOOKUP_CATEGORIES = 'inventory:lookup:categories';

    public const LOOKUP_STORAGE_LOCATIONS = 'inventory:lookup:storage-locations';

    public const LOOKUP_SUPPLIERS = 'inventory:lookup:suppliers';

    /** Lookups that also depend on stock, so they follow the ledger version too. */
    public const LOOKUP_PORTAL_ITEMS = 'inventory:lookup:portal-items';

    public const LOOKUP_TRANSACTION_USERS = 'inventory:lookup:transaction-users';

    /** Whether a bump is already scheduled for the current transaction's commit. */
    private static bool $pendingBump = false;

    public static function ledgerVersion(): string
    {
        return (string) Cache::get(self::LEDGER_VERSION, '0');
    }

    /**
     * Invalidate everything tagged with the ledger version once the current
     * transaction commits (immediately outside one; never on rollback).
     * A unique value rather than an increment: the file store's increment
     * isn't atomic, and two concurrent bumps must never land on one version.
     */
    public static function bumpLedger(): void
    {
        if (DB::transactionLevel() === 0) {
            self::writeLedgerVersion();

            return;
        }

        // Inside a transaction: one write when the outermost transaction
        // commits, however many rows it touches. resetPendingBump() (wired in
        // AppServiceProvider to rollbacks and queue job ends) makes sure a
        // dropped callback can never leave this stuck.
        if (self::$pendingBump) {
            return;
        }

        self::$pendingBump = true;

        DB::afterCommit(function (): void {
            self::$pendingBump = false;
            self::writeLedgerVersion();
        });
    }

    /**
     * Forget a scheduled bump whose afterCommit callback may have been
     * dropped (any rollback, including a savepoint) or that may belong to a
     * finished queue job. At worst the next write bumps once more.
     */
    public static function resetPendingBump(): void
    {
        self::$pendingBump = false;
    }

    private static function writeLedgerVersion(): void
    {
        Cache::forever(self::LEDGER_VERSION, (string) Str::ulid());
    }

    /**
     * Remember a value tagged with the current ledger version.
     *
     * @template TValue of array|int|float|string|bool|null
     *
     * @param  array<array-key, mixed>  $params  everything the value depends on besides the data
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function ledger(string $name, array $params, Closure $callback): mixed
    {
        return self::versioned(
            sprintf('inventory:%s:%s', $name, md5(serialize($params))),
            self::ledgerVersion(),
            self::LEDGER_TTL,
            $callback,
        );
    }

    /**
     * Remember a lookup list. With $followsLedger it is also rebuilt when the
     * ledger version changes (lists that depend on stock or the ledger).
     *
     * @template TValue of array
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function lookup(string $key, Closure $callback, bool $followsLedger = false): array
    {
        return self::versioned($key, $followsLedger ? self::ledgerVersion() : '', self::LOOKUP_TTL, $callback);
    }

    /**
     * A lookup list of models: cached as raw attributes, rehydrated on read,
     * so callers get the same models (with casts) as a direct query.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @param  Closure(): EloquentCollection<int, TModel>  $query
     * @return EloquentCollection<int, TModel>
     */
    public static function models(string $key, string $model, Closure $query, bool $followsLedger = false): EloquentCollection
    {
        return $model::hydrate(self::lookup(
            $key,
            fn (): array => $query()->map(fn (Model $row) => $row->getAttributes())->all(),
            $followsLedger,
        ));
    }

    /*
     * The shared lookup lists. Each key has exactly one loader, so every caller
     * gets the same list and filters or plucks it as needed.
     */

    /** @return EloquentCollection<int, InventoryCategories> all categories, by name */
    public static function categories(): EloquentCollection
    {
        return self::models(self::LOOKUP_CATEGORIES, InventoryCategories::class,
            fn () => InventoryCategories::query()->orderBy('name')->get());
    }

    /** @return EloquentCollection<int, StorageLocations> all storage locations, by name */
    public static function storageLocations(): EloquentCollection
    {
        return self::models(self::LOOKUP_STORAGE_LOCATIONS, StorageLocations::class,
            fn () => StorageLocations::query()->orderBy('name')->get());
    }

    /** @return EloquentCollection<int, Suppliers> all suppliers, by name */
    public static function suppliers(): EloquentCollection
    {
        return self::models(self::LOOKUP_SUPPLIERS, Suppliers::class,
            fn () => Suppliers::query()->orderBy('name')->get());
    }

    /** @return EloquentCollection<int, InventoryItems> active items with available_qty, by name */
    public static function activeItemsWithStock(): EloquentCollection
    {
        return self::models(self::LOOKUP_PORTAL_ITEMS, InventoryItems::class,
            fn () => InventoryItems::query()->where('is_active', true)->withAvailableQty()->orderBy('name')->get(),
            followsLedger: true);
    }

    /** @return EloquentCollection<int, User> users who have ledger entries (id, name), by name */
    public static function transactionUsers(): EloquentCollection
    {
        return self::models(self::LOOKUP_TRANSACTION_USERS, User::class,
            fn () => User::query()
                ->whereIn('id', InventoryTransactions::query()->select('user_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            followsLedger: true);
    }

    /**
     * Forget a lookup list once the current transaction commits.
     */
    public static function forgetLookup(string $key): void
    {
        DB::afterCommit(fn () => Cache::forget($key));
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    private static function versioned(string $key, string $version, int $ttl, Closure $callback): mixed
    {
        $cached = Cache::get($key);

        if (is_array($cached) && ($cached['v'] ?? null) === $version && array_key_exists('data', $cached)) {
            return $cached['data'];
        }

        $data = $callback();
        Cache::put($key, ['v' => $version, 'data' => $data], $ttl);

        return $data;
    }
}
