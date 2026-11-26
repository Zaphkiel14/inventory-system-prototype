<?php

use App\Livewire\Monitoring\ExpirationMonitoring;
use App\Models\InventoryBatches;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * The expiry scopes compare the raw expiration_date column against day bounds.
 * Rows are stored either as "Y-m-d" (seeders, raw inserts) or "Y-m-d 00:00:00"
 * (the model's date cast), so every boundary is checked in both formats. The
 * clock sits late in the evening so a comparison against now() instead of
 * today() would show up.
 */

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 6, 15)->setTime(23, 30));
});

dataset('stored date formats', [
    'Y-m-d' => 'Y-m-d',
    'Y-m-d 00:00:00' => 'Y-m-d 00:00:00',
]);

function batchExpiringIn(int $days, string $format): InventoryBatches
{
    $batch = InventoryBatches::factory()->create(['received_qty' => 5, 'remaining_qty' => 5]);

    DB::table('inventory_batches')
        ->where('id', $batch->id)
        ->update(['expiration_date' => today()->addDays($days)->format($format)]);

    return $batch;
}

/**
 * @param  callable(Builder<InventoryBatches>): mixed  $scope
 */
function scopeIncludes(InventoryBatches $batch, callable $scope): bool
{
    return tap(InventoryBatches::query(), $scope)->whereKey($batch->id)->exists();
}

it('treats a batch expiring today as usable and not expired', function (string $format) {
    $batch = batchExpiringIn(0, $format);

    expect(scopeIncludes($batch, fn ($query) => $query->usable()))->toBeTrue()
        ->and(scopeIncludes($batch, fn ($query) => $query->expired()))->toBeFalse();
})->with('stored date formats');

it('treats a batch that expired yesterday as expired and not usable', function (string $format) {
    $batch = batchExpiringIn(-1, $format);

    expect(scopeIncludes($batch, fn ($query) => $query->expired()))->toBeTrue()
        ->and(scopeIncludes($batch, fn ($query) => $query->usable()))->toBeFalse();
})->with('stored date formats');

it('includes the last day of the band and excludes the day after', function (string $format, int $days) {
    $lastDay = batchExpiringIn($days, $format);
    $dayAfter = batchExpiringIn($days + 1, $format);
    $today = batchExpiringIn(0, $format);

    expect(scopeIncludes($lastDay, fn ($query) => $query->expiringWithin($days)))->toBeTrue()
        ->and(scopeIncludes($today, fn ($query) => $query->expiringWithin($days)))->toBeTrue()
        ->and(scopeIncludes($dayAfter, fn ($query) => $query->expiringWithin($days)))->toBeFalse();
})->with('stored date formats')->with([7, 30, 90]);

it('lists a batch 90 days out in the 90-day band and one 91 days out under later', function (string $format) {
    $this->actingAs(User::factory()->create());
    $ninety = batchExpiringIn(90, $format);
    $ninetyOne = batchExpiringIn(91, $format);

    $listed = fn (string $status) => Livewire::withoutLazyLoading()->test(ExpirationMonitoring::class)
        ->set('status', $status)
        ->instance()
        ->batches
        ->pluck('id')
        ->all();

    expect($listed('90'))->toContain($ninety->id)->not->toContain($ninetyOne->id)
        ->and($listed('later'))->toContain($ninetyOne->id)->not->toContain($ninety->id);
})->with('stored date formats');
