<?php

namespace App\Console\Commands;

use App\Chat\Client as IrcClient;
use App\Models\Client;
use App\Models\Instance;
use App\Models\Network;
use App\Models\Nick;
use App\SystemMessage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;

/**
 * Command to instantiate an IRC client.
 */
class MakeInstance extends Command
{
    /**
     * Application cache repository.
     *
     * @var Repository
     */
    protected $cache;

    /**
     * The Nick instance selected for the run.
     *
     * @var Nick|null
     */
    protected $nick;

    /**
     * The Network instance selected for the run.
     *
     * @var Network|null
     */
    protected $network;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:make-instance {--nick=} {--network=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instantiates an IRC client.';

    /**
     * MakeInstance constructor.
     */
    public function __construct(Repository $cache)
    {
        parent::__construct();
        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     */
    public function handle(SystemMessage $systemMessage)
    {
        $nick = $this->getNick();
        $network = $this->getNetwork();

        if (! $nick || ! $network) {
            $this->error('A valid --nick and --network are required.');

            return;
        }

        $liveInstance = $this->checkLiveInstance($nick, $network);

        if ($liveInstance) {
            $this->info("Live instance id: {$liveInstance->id} status: {$liveInstance->status} found for {$nick->nick}");
        }

        // Instantiate and connect the IRC client
        $client = new IrcClient($nick, $network, $this->cache, $this, $systemMessage);
        $client->connect();
    }

    /**
     * Checks if a live instance exists or creates a new one.
     */
    protected function checkLiveInstance(Nick $nick, Network $network): ?Instance
    {
        // Create or update the client for the specified network and nick
        $client = Client::updateOrCreate(
            ['network_id' => $network->id, 'nick_id' => $nick->id],
            ['enabled' => true]
        );

        if (! $client) {
            return null;
        }

        // Retrieve or create the associated instance with the current PID
        $pid = getmypid();

        return Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP, 'enabled' => true, 'pid' => $pid]
        );
    }

    /**
     * Retrieves the Nick instance based on the provided option.
     */
    protected function getNick(): ?Nick
    {
        if ($this->nick !== null) {
            return $this->nick;
        }

        $nickName = $this->option('nick');

        if (! $nickName) {
            $this->error('A valid --nick is required.');

            return null;
        }

        return $this->nick = Nick::where('nick', $nickName)->first();
    }

    /**
     * Retrieves the Network instance based on the provided option.
     */
    protected function getNetwork(): ?Network
    {
        if ($this->network !== null) {
            return $this->network;
        }

        $networkName = $this->option('network');

        if (! $networkName) {
            $this->error('A valid --network is required.');

            return null;
        }

        return $this->network = Network::where('name', $networkName)->first();
    }
}
