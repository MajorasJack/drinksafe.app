<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use DrinkSafe\Venues\Commands\FetchVenuesCommand;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->loadDrinkSafeMigrations();
        $this->registerCommands();
    }

    /**
     * Register DrinkSafe module commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FetchVenuesCommand::class,
            ]);
        }
    }

    /**
     * Load DrinkSafe module migrations.
     */
    protected function loadDrinkSafeMigrations(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/drinksafe'));
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
