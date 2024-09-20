<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
