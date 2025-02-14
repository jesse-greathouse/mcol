<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider,
    Illuminate\Support\Facades\Cache,
    Illuminate\Support\Facades\Event,
    Illuminate\Support\Facades\Redis;

use App\Events\PacketSearchResult,
    App\Events\PacketSearchSummary,
    App\Listeners\SendPacketSearchResultMessage,
    App\Listeners\SendPacketSearchSummaryMessage;


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
            PacketSearchResult::class,
            SendPacketSearchResultMessage::class,
        );

        Event::listen(
            PacketSearchSummary::class,
            SendPacketSearchSummaryMessage::class,
        );
    }
}
