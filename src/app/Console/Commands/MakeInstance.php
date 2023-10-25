<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;

use App\Chat\Client\PacketLocatorClient as Client;

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

        $client = new Client($nick, $network, $this);
        $client->connect();
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
