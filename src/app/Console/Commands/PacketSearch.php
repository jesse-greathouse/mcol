<?php

namespace App\Console\Commands;

use App\Jobs\PacketSearch as PacketSearchJob;
use App\Models\Channel;
use App\Models\Network;
use App\Models\PacketSearch as PacketSearchModel;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\search;

/**
 * Handles the packet search operation.
 */
class PacketSearch extends Command implements PromptsForMissingInput
{
    // Constants for search parameters.
    public const INTERVAL = 1;

    public const MAX_RUNTIME = 30;

    /**
     * @var float Time when the search started.
     */
    protected $startTime;

    /**
     * @var PacketSearchModel Previous packet search instance.
     */
    protected $oldPacketSearch;

    /**
     * @var string Search query string.
     */
    protected $searchStr;

    /**
     * @var Network Selected network instance.
     */
    protected $network;

    /**
     * @var Channel Selected channel instance.
     */
    protected $channel;

    /**
     * @var string Command signature for the packet search.
     */
    protected $signature = 'mcol:packet-search {network} {channel} {searchStr}';

    /**
     * @var string Command description.
     */
    protected $description = 'Issues a packet search in the IRC rooms of an instance.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $network = $this->getNetwork();
        $channel = $this->getChannel();
        $searchStr = $this->getSearchStr();

        if ($network === null || $channel === null || $searchStr === null) {
            $this->error('Missing arguments.');

            return;
        }

        $this->warn("Searching for: $searchStr ...");

        // Dispatch the job for packet search.
        PacketSearchJob::dispatch($network, $channel, $searchStr);

        $this->startTime = microtime(true);

        $oldPacketSearch = $this->getOldPacketSearch();

        while (true) {
            sleep(self::INTERVAL);

            $packetSearch = $this->getNewPacketSearch($oldPacketSearch);

            if ($packetSearch !== null) {
                $this->showSearchResults($packetSearch);
                break;
            }

            // Timeout prevention
            $timeNow = microtime(true);
            if (($timeNow - $this->startTime) >= self::MAX_RUNTIME) {
                $this->error('Search timed out.');

                return; // Failure, automatic exit code 1
            }
        }

        $this->info('Search completed successfully.'); // This signals a successful completion
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
     * Returns the Search string.
     */
    protected function getSearchStr(): ?string
    {
        if ($this->searchStr === null) {
            $searchStr = $this->argument('searchStr');

            if ($searchStr === null || trim($searchStr) === '') {
                $this->error('A valid search string is required.');

                return null;
            }

            if (is_array($searchStr)) {
                $searchStr = implode(' ', $searchStr);
            }

            $this->searchStr = $searchStr;
        }

        return $this->searchStr;
    }

    /**
     * Returns the most recent PacketSearch object.
     */
    protected function getOldPacketSearch(): ?PacketSearchModel
    {
        if ($this->oldPacketSearch === null) {
            $this->oldPacketSearch = PacketSearchModel::orderBy('created_at', 'DESC')->first();
        }

        return $this->oldPacketSearch;
    }

    /**
     * Finds a packet search newer than the old one.
     */
    protected function getNewPacketSearch(?PacketSearchModel $oldPacketSearch = null): ?PacketSearchModel
    {
        if ($oldPacketSearch !== null) {
            return PacketSearchModel::where('created_at', '>', $oldPacketSearch->created_at)
                ->orderBy('created_at', 'DESC')
                ->first();
        }

        return PacketSearchModel::orderBy('created_at', 'DESC')->first();
    }

    /**
     * Displays the results of a packet search.
     */
    protected function showSearchResults(PacketSearchModel $packetSearch): void
    {
        $this->warn("Found {$packetSearch->packetSearchResults->count()} results in {$packetSearch->channel->name}");

        if ($packetSearch->packetSearchResults->count() > 0) {
            $tableHeader = ['id', 'size', 'file'];
            $tableBody = [];

            foreach ($packetSearch->packetSearchResults as $result) {
                $tableBody[] = [
                    $result->packet->id,
                    $result->packet->size,
                    $result->packet->file_name,
                ];
            }

            $this->table($tableHeader, $tableBody);
        }

        $this->newLine();
    }
}
