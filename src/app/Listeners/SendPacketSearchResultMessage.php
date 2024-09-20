<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\PacketSearchResult,
    App\Models\PacketSearchResult as SearchResult;

class SendPacketSearchResultMessage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PacketSearchResult $event): void
    {
        //
    }
}
