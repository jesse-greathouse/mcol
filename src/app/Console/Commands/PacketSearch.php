<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use function Laravel\Prompts\search;

use App\Jobs\PacketSearch as PacketSearchJob,
    App\Models\Channel,
    App\Models\PacketSearch as PacketSearchModel,
    App\Models\Network;

class PacketSearch extends Command implements PromptsForMissingInput
{
    const INTERVAL = 1;
    const MAX_RUNTIME = 30;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var PacketSearchModel
     */
    protected $oldPacketSearch;

    /**
     * searchStr string
     *
     * @var string
     */
    protected $searchStr;

    /**
     * network selected for run
     *
     * @var Network
     */
    protected $network;

    /**
     * channel selected for run
     *
     * @var Channel
     */
    protected $channel;

    /**
     * @var string
     */
    protected $signature = 'mcol:packet-search {network} {channel} {searchStr}';

    /**
     * @var string
     */
    protected $description = 'Issues a packet search in the IRC rooms of an instance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $network = $this->getNetwork();
        $channel = $this->getChannel();
        $searchStr = $this->getSearchStr();

        if (null === $network || null === $channel || null === $searchStr) {
            return 1;
        }

        $oldPacketSearch = $this->getOldPacketSearch();

        $this->warn("Searching for: $searchStr ...");

        PacketSearchJob::dispatch($network, $channel, $searchStr);

        $this->startTime = microtime(true);

        while (true) {
            sleep(self::INTERVAL);

            $packetSearch = $this->getNewPacketSearch($oldPacketSearch);

            if (null !== $packetSearch) {
                $this->showSearchResults($packetSearch);
                break;
            }

            // Infinite loop prevention.
            $timeNow = microtime(true);
            if (($timeNow - $this->startTime) >= self::MAX_RUNTIME) {
                $this->error("Search timed out.");
                break;
            }
        }

        $this->warn("... done!");
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
     *
     * @return Network|null
     */
    protected function getNetwork(): Network|null
    {
        if (null === $this->network) {
            $name = $this->argument('network');

            if (null === $name) {
                $this->error('A valid --network is required.');
                return null;
            }

            $network = Network::where('name', $name)->first();

            if (null === $network) {
                $this->error('A valid --network is required.');
                return null;
            }

            $this->network = $network;
        }

        return $this->network;
    }

    /**
     * Returns an instance of Channel by any given name.
     *
     * @return Channel|null
     */
    protected function getChannel(): Channel|null
    {
        if (null === $this->channel) {
            $name = $this->argument('channel');

            if (null === $name) {
                $this->error('A valid --channel is required.');
                return null;
            }

            $channel = Channel::where('name', $name)->first();

            if (null === $channel) {
                $this->error('A valid --channel is required.');
                return null;
            }

            $this->channel = $channel;
        }

        return $this->channel;
    }

    /**
     * Returns the Search string.
     *
     * @return string|null
     */
    protected function getSearchStr(): string|null
    {
        if (null === $this->searchStr) {
            $searchStr = $this->argument('searchStr');

            if (null === $searchStr || '' === trim($searchStr)) {
                $this->error('A valid search string is required.');
                return null;
            } else if (is_array($searchStr)) {
                $searchStr = implode(' ', $searchStr);
            }

            $this->searchStr = $searchStr;
        }

        return $this->searchStr;
    }

    /**
     * Returns the most recent PacketSearch object.
     *
     * @return PacketSearchModel|null
     */
    protected function getOldPacketSearch(): PacketSearchModel|null
    {
        if (null === $this->oldPacketSearch) {
            $this->oldPacketSearch = PacketSearchModel::orderBy('created_at', 'DESC')->first();
        }

        return $this->oldPacketSearch;
    }

    /**
     * Finds a packet search newer than the old one.
     *
     * @param PacketSearchModel|null $oldPacketSearch
     * @return PacketSearchModel|null
     */
    protected function getNewPacketSearch(PacketSearchModel $oldPacketSearch = null): PacketSearchModel|null
    {
        if (null !== $oldPacketSearch) {
            return PacketSearchModel::where('created_at', '>', $oldPacketSearch->created_at)
                ->orderBy('created_at', 'DESC')
                ->first();
        } else {
            return PacketSearchModel::orderBy('created_at', 'DESC')->first();
        }
    }

    protected function showSearchResults(PacketSearchModel $packetSearch): void
    {
        $this->warn("Found {$packetSearch->packetSearchResults->count()} results in {$packetSearch->channel->name}");

        if ($packetSearch->packetSearchResults->count() > 0) {
            $tableHeader = ['id', 'size', 'file'];
            $tableBody = [];

            foreach($packetSearch->packetSearchResults as $result) {
                array_push($tableBody, [
                    $result->packet->id,
                    $result->packet->size,
                    $result->packet->file_name,
                ]);
            }

            $this->table($tableHeader, $tableBody);
        }

        $this->newLine();
    }
}
