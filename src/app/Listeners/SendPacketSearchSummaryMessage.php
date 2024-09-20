<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

use  App\Events\PacketSearchSummary,
     App\Models\PacketSearchResult;

class SendPacketSearchSummaryMessage
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
    public function handle(PacketSearchSummary $event): void
    {
        $packetSearch = $event->packetSearch;
        $packetSearchId = $packetSearch->id;

        // Find all orphan search results and bind them to this Search.
        foreach (PacketSearchResult::whereNull('packet_search_id')->get() as $packetSearchResult) {
            $packetSearchResult->packet_search_id = $packetSearchId;
            $packetSearchResult->save();
        }
    }
}
