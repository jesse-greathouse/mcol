<?php

namespace App\Console\Commands;

use App\Jobs\HotReport as HotReportJob;
use App\Models\Channel;
use App\Models\HotReport;
use App\Models\HotReportLine;
use App\Models\Network;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\search;

class ReportHot extends Command implements PromptsForMissingInput
{
    // Constant values
    const INTERVAL = 1;

    const WAIT_FOR_COMPLETION = 3;

    const MAX_RUNTIME = 30;

    /** @var float */
    protected $startTime;

    /** @var HotReport */
    protected $oldHotReport;

    /** @var Network Network selected for run */
    protected $network;

    /** @var Channel Channel selected for run */
    protected $channel;

    /** @var string */
    protected $signature = 'mcol:hot {network} {channel}';

    /** @var string */
    protected $description = 'Request a report of the hottest search terms of a given channel.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $network = $this->getNetwork();
        $channel = $this->getChannel();

        if ($network === null || $channel === null) {
            return 1;
        }

        $oldHotReport = $this->getOldHotReport();

        $this->warn("Looking for the hottest search terms on: {$channel->name}@{$network->name} ...");

        HotReportJob::dispatch($network, $channel);

        $this->startTime = microtime(true);

        while (true) {
            sleep(self::INTERVAL);

            $hotReport = $this->getNewHotReport($oldHotReport);

            if ($hotReport !== null) {
                $this->showReportResults($hotReport);
                break;
            }

            // Infinite loop prevention.
            $timeNow = microtime(true);
            if (($timeNow - $this->startTime) >= self::MAX_RUNTIME) {
                $this->error('Report timed out.');
                break;
            }
        }

        $this->warn('... done!');

        return 0;
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'network' => fn () => search(
                label: 'Pick A Network:',
                placeholder: 'Abjects',
                options: fn ($networkValue) => strlen($networkValue) > 0
                    ? Network::where('name', 'like', "%{$networkValue}%")->pluck('name')->all()
                    : []
            ),
            'channel' => fn () => search(
                label: 'Pick a Channel:',
                placeholder: '#mg-chat',
                options: fn ($channelValue) => strlen($channelValue) > 0
                    ? Channel::where('name', 'like', "%{$channelValue}%")
                        ->whereNotNull('channel_id')
                        ->orderBy('id')
                        ->pluck('name')->all()
                    : []
            ),
            'searchStr' => ['Enter a search phrase:', 'E.g. Better Call Saul'],
        ];
    }

    /**
     * Returns an instance of Network by any given name.
     */
    protected function getNetwork(): ?Network
    {
        if ($this->network === null) {
            $name = $this->argument('network');

            if ($name === null) {
                $this->error('A valid --network is required.');

                return null;
            }

            $network = Network::where('name', $name)->first();

            if ($network === null) {
                $this->error('A valid --network is required.');

                return null;
            }

            $this->network = $network;
        }

        return $this->network;
    }

    /**
     * Returns an instance of Channel by any given name.
     */
    protected function getChannel(): ?Channel
    {
        if ($this->channel === null) {
            $name = $this->argument('channel');

            if ($name === null) {
                $this->error('A valid --channel is required.');

                return null;
            }

            $channel = Channel::where('name', $name)->first();

            if ($channel === null) {
                $this->error('A valid --channel is required.');

                return null;
            }

            $this->channel = $channel;
        }

        return $this->channel;
    }

    /**
     * Returns the most recent Hot Report object.
     */
    protected function getOldHotReport(): ?HotReport
    {
        if ($this->oldHotReport === null) {
            $this->oldHotReport = HotReport::orderBy('created_at', 'DESC')->first();
        }

        return $this->oldHotReport;
    }

    /**
     * Finds a Hot Report newer than the old one.
     */
    protected function getNewHotReport(?HotReport $oldHotReport = null): ?HotReport
    {
        if ($oldHotReport !== null) {
            return HotReport::where('created_at', '>', $oldHotReport->created_at)
                ->orderBy('created_at', 'DESC')
                ->first();
        }

        return HotReport::orderBy('created_at', 'DESC')->first();
    }

    /**
     * Displays the report results in the console.
     */
    protected function showReportResults(HotReport $hotReport): void
    {
        $this->warn("What's hot in {$hotReport->channel->name}: {$hotReport->summary}");
        sleep(self::WAIT_FOR_COMPLETION);

        $lines = HotReportLine::where('hot_report_id', $hotReport->id)->orderByDesc('rating')->get();

        if ($lines->count() > 0) {
            $rank = 1;
            $tableHeader = ['rank', 'rating', 'term'];
            $tableBody = [];

            foreach ($lines as $result) {
                $tableBody[] = [
                    $rank,
                    $result->rating,
                    $result->term,
                ];

                // Increment the rank
                $rank++;
            }

            $this->table($tableHeader, $tableBody);
        }

        $this->newLine();
    }
}
