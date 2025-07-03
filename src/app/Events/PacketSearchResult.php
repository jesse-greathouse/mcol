<?php

namespace App\Events;

use App\Models\PacketSearchResult as SearchResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event triggered when a packet search result is to be broadcasted.
 */
class PacketSearchResult
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var SearchResult The search result associated with the packet search.
     */
    public SearchResult $packetSearchResult;

    /**
     * Constructor for the PacketSearchResult event.
     *
     * @param  SearchResult  $packetSearchResult  The search result to be included in the event.
     */
    public function __construct(SearchResult $packetSearchResult)
    {
        $this->packetSearchResult = $packetSearchResult;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * This method defines the broadcast channels for the packet search result event.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel> The channels the event will be broadcasted to.
     */
    public function broadcastOn(): array
    {
        // Returning an array with a single PrivateChannel for packet search result
        return [
            new PrivateChannel('packet-search-result'),
        ];
    }
}
