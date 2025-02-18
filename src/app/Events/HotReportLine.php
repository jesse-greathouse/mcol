<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets,
    Illuminate\Broadcasting\PrivateChannel,
    Illuminate\Foundation\Events\Dispatchable,
    Illuminate\Queue\SerializesModels;

use App\Models\HotReportLine as ReportLine;

class HotReportLine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var ReportLine The hot report line instance */
    public ReportLine $hotReportLine;

    /**
     * Create a new event instance.
     *
     * @param ReportLine $hotReportLine
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
