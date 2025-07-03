<?php

namespace App\Events;

use App\Models\HotReportLine as ReportLine;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HotReportLine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var ReportLine The hot report line instance */
    public ReportLine $hotReportLine;

    /**
     * Create a new event instance.
     */
    public function __construct(ReportLine $hotReportLine)
    {
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
