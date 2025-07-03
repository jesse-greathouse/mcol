<?php

namespace App\Providers;

use App\Media\Service\Plex;
use App\Settings;
use Illuminate\Support\ServiceProvider;

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
