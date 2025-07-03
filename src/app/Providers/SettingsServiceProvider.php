<?php

namespace App\Providers;

use App\Settings;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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
