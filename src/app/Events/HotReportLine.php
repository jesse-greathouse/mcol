<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\HotReportLine as ReportLine;

class HotReportLine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var ReportLine
     */
    public ReportLine $hotReportLine;

    public function __construct(ReportLine $hotReportLine) {
        $this->hotReportLine = $hotReportLine;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hot-report-line'),
        ];
    }
}
