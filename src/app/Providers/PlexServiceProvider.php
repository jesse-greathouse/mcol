<?php

namespace App\Providers;

use Illuminate\Contracts\Foundation\Application,
    Illuminate\Support\ServiceProvider;

use App\Media\Service\Plex,
    App\Settings;

class PlexServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Plex::class, function (Settings $settings) {
            return new Plex($settings);
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
