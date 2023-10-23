<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

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
     * network selected for run
     *
     * @var Channel
     */
    protected $channel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:make-instance {--nick=} {--network=} {--channel=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $nick = $this->getNick();
        if (!$nick) $this->error('A valid --nick is required.');
        
        $network = $this->getNetwork();
        if (!$network) $this->error('A valid --network is required.');
        
        $channel = $this->getChannel();
        if (!$channel) $this->error('A valid --$channel is required.');

        if (!$nick || !$network || !$channel) return;

        $options = new ClientOptions($nick->nick, [$channel->name]);

        $client = new IrcClient("{$network->firstServer->host}:6667", $options);
        
        $client->on('registered', function() {
            $this->info('connected');
        });

        $client->on('names', function (IrcChannel $channel) {
            $userList = $channel->getUsers();
            if (count($userList) > 0) {
                $this->table(['num', 'users'], $userList);
            }
        });

        $client->connect();

        $client->send('/names');
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


    /**
     * Returns an instance of Channel by any given name.
     *
     * @return Channel|null
     */
    protected function getChannel(): Channel|null
    {
        if (null === $this->channel) {
            $name = $this->option('channel');

            if (null === $name) {
                $this->error('A valid --channel is required.');
            }

            $this->channel = Channel::where('name', $name)->first();
        }

        return $this->channel;
    }
}
