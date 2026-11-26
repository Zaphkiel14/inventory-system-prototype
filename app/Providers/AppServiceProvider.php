<?php

namespace App\Providers;

use App\Services\Inventory\StockStatusMonitor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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

        $this->registerTelescope();
    }

    /**
     * Register Telescope when TELESCOPE_ENABLED=true (off by default), in any
     * environment. It isn't auto-discovered, so it loads only from here.
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
