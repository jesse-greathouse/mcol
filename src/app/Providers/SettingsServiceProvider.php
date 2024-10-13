<?php

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application,
    Illuminate\Support\ServiceProvider;

use App\Settings;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class, function (Application $app) {
            return new Settings($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
