<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Media\Service\Plex,
    App\Settings;

class PlexServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Plex::class, function ($app) {
            return new Plex($app->make(Settings::class));
        });
    }
}
