<?php

namespace App\Events;

use App\Models\HotReport;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event triggered when a hot report summary is to be broadcasted.
 */
class HotReportSummary implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var HotReport The hot report associated with the summary.
     */
    public HotReport $hotReport;

    /**
     * Constructor for the HotReportSummary event.
     *
     * @param  HotReport  $hotReport  The hot report to be included in the summary.
     */
    public function __construct(HotReport $hotReport)
    {
        $this->hotReport = $hotReport;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * This method defines which channels the event will be broadcasted on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel> The channels the event will be broadcasted to.
     */
    public function broadcastOn(): array
    {
        // Returning an array with a single PrivateChannel
        return [
            new PrivateChannel('hot-report-summary'),
        ];
    }
}
