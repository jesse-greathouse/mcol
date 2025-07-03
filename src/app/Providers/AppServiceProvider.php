<?php

namespace App\Providers;

use App\Events\PacketSearchSummary;
use App\Listeners\SendPacketSearchSummaryMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

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
        try {
            // Test the Redis connection
            Redis::connection()->ping();
            Cache::extend('fallback', function ($app) {
                return Cache::store('redis');
            });
        } catch (\Exception $e) {
            // Redis is unavailable, fall back to file cache
            Cache::extend('fallback', function ($app) {
                return Cache::store('file');
            });
        }

        Event::listen(
            PacketSearchSummary::class,
            SendPacketSearchSummaryMessage::class,
        );
    }
}
