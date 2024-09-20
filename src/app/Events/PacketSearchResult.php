<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\PacketSearchResult as SearchResult;

class PacketSearchResult
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var SearchResult
     */
    public SearchResult $packetSearchResult;

    public function __construct(SearchResult $packetSearchResult) {
        $this->packetSearchResult = $packetSearchResult;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('packet-search-result'),
        ];
    }
}
