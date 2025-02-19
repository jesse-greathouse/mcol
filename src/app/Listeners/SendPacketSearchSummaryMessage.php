<?php

namespace App\Listeners;

use  App\Events\PacketSearchSummary,
     App\Models\PacketSearchResult;

class SendPacketSearchSummaryMessage
{
    /**
     * Handle the event.
     */
    public function handle(PacketSearchSummary $event): void
    {
        // Update orphan search results
        PacketSearchResult::whereNull('packet_search_id')
            ->update(['packet_search_id' => $event->packetSearch->id]);
    }
}
