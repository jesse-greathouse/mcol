<?php

namespace App\Events;

use App\Models\PacketSearch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class PacketSearchSummary
 *
 * This event represents the summary of a packet search and broadcasts on a private channel.
 */
class PacketSearchSummary implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var PacketSearch The packet search associated with the event.
     */
    public PacketSearch $packetSearch;

    /**
     * PacketSearchSummary constructor.
     *
     * @param  PacketSearch  $packetSearch  The packet search instance.
     */
    public function __construct(PacketSearch $packetSearch)
    {
        $this->packetSearch = $packetSearch;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * This method defines the private channel the event will be broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel> The broadcast channels.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('packet-search-summary'),  // Broadcasting on a private channel for security
        ];
    }
}
