<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Chat\Log\Mapper,
    App\Chat\Log\Streamer,
    App\Models\Nick;

class ChatLogStreamProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Streamer::class, function () {
            $mappers = [];
            $nicks = Nick::all();
            $logRoot = env('LOG_DIR', '/var/log');

            // Build Mappers
            foreach($nicks as $nick) {
                $networkName = $nick->network->name;
                $mappers[$networkName] = new Mapper($logRoot, $networkName, $nick->nick);
            }

            return new Streamer($mappers);
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
