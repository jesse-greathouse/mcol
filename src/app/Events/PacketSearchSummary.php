<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets,
    Illuminate\Contracts\Broadcasting\ShouldBroadcast,
    Illuminate\Broadcasting\PrivateChannel,
    Illuminate\Foundation\Events\Dispatchable,
    Illuminate\Queue\SerializesModels;

use App\Models\PacketSearch;

/**
 * Class PacketSearchSummary
 *
 * This event represents the summary of a packet search and broadcasts on a private channel.
 *
 * @package App\Events
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
     * @param PacketSearch $packetSearch The packet search instance.
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
