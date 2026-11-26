<?php

namespace App\Providers;

use App\Services\AdministrativeAreas\AdministrativeAreaService;
use App\Services\Inventory\StockStatusMonitor;
use App\Support\InventoryCache;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StockStatusMonitor::class);

        // One instance per request / queue job, so its in-memory area list
        // never outlives the request (scoped instances are flushed per job).
        $this->app->scoped(AdministrativeAreaService::class);

        $this->registerTelescope();
    }

    /**
     * Register Telescope only when telescope.enabled is true (TELESCOPE_ENABLED,
     * never in production). It isn't auto-discovered, so it loads only from here.
     */
    protected function registerTelescope(): void
    {
        if (config('telescope.enabled')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureBlaze();
        $this->configureLedgerBumps();
    }

    /**
     * InventoryCache::bumpLedger() schedules one version write per outermost
     * commit behind a static flag. Clear the flag whenever its callback may
     * have been dropped (any rollback, savepoints included) and at the end of
     * every queue job, so a long-running worker can never stop bumping.
     */
    protected function configureLedgerBumps(): void
    {
        InventoryCache::resetPendingBump();

        Event::listen(TransactionRolledBack::class, fn () => InventoryCache::resetPendingBump());
        Event::listen(JobProcessed::class, fn () => InventoryCache::resetPendingBump());
        Event::listen(JobFailed::class, fn () => InventoryCache::resetPendingBump());
    }

    /**
     * Compile all of the app's anonymous Blade components with Blaze.
     */
    protected function configureBlaze(): void
    {
        Blaze::optimize()->in(resource_path('views/components'));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Outside production: throw on lazy loading, on filling unfillable
        // attributes, and on reading attributes that weren't selected.
        Model::shouldBeStrict(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
