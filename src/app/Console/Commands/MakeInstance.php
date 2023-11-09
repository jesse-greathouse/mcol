<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Client,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Network;

use App\Chat\Client\PacketLocatorClient as IrcClient;

class MakeInstance extends Command
{
    /**
     * Nick selected for run
     *
     * @var Nick
     */
    protected $nick;

    /**
     * network selected for run
     *
     * @var Network
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
     * Execute the console command.
     */
    public function handle()
    {

        $nick = $this->getNick();
        if (!$nick) $this->error('A valid --nick is required.');
        
        $network = $this->getNetwork();
        if (!$network) $this->error('A valid --network is required.');
        
        if (!$nick || !$network) return;

        $liveInstance = $this->liveInstanceCheck($nick, $network);

        if (null !== $liveInstance) {
            $this->info("Live instance id: {$liveInstance->id} status: {$liveInstance->status} found for $nick->nick");
        }

        $client = new IrcClient($nick, $network, $this);
        $client->connect();
    }

    /**
     * Return an instance of a "liveInstance" or null.
     *
     * @param Nick $nick
     * @param Network $network
     * @return Instance|null
     */
    protected function liveInstanceCheck(Nick $nick, Network $network): Instance|null
    {
        $client = Client::updateOrCreate(
            ['network_id' => $network->id, 'nick_id' => $nick->id],
            ['enabled' => true]
        );

        if ($client) {
            $pid = getmypid();
            $instance = Instance::updateOrCreate(
                ['client_id' => $client->id],
                ['status' => Instance::STATUS_UP, 'enabled' => true, 'pid' => $pid]
            );

            return $instance;
        }

        return null;
    }

    /**
     * Returns an instance of Nick by any given name.
     *
     * @return Nick|null
     */
    protected function getNick(): Nick|null
    {
        if (null === $this->nick) {
            $name = $this->option('nick');

            if (null === $name) {
                $this->error('A valid --nick is required.');
            }

            $this->nick = Nick::where('nick', $name)->first();
        }

        return $this->nick;
    }


    /**
     * Returns an instance of Network by any given name.
     *
     * @return Network|null
     */
    protected function getNetwork(): Network|null
    {
        if (null === $this->network) {
            $name = $this->option('network');

            if (null === $name) {
                $this->error('A valid --network is required.');
            }

            $this->network = Network::where('name', $name)->first();
        }

        return $this->network;
    }
}
