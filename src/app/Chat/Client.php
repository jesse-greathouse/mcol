<?php

namespace App\Chat;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;

class Client 
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
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    /**
     * PacketLocator instance for this client.
     *
     * @var PacketLocator
     */
    protected $packetLocator;

    /**
     * IRC client
     * 
     * @var IrcClient
     */
    protected $client;

    public function __construct(Nick $nick, Network $network, Channel $channel, Command $console) {
        $this->nick = $nick;
        $this->network = $network;
        $this->channel = $channel;
        $this->console = $console;
        $this->packetLocator = new PacketLocator();

        $options = new ClientOptions($nick->nick, [$channel->name]);

        $this->client = new IrcClient("{$network->firstServer->host}:6667", $options);
        $this->assignHandlers();
    }

    protected function assignHandlers(): void
    {
        $this->registeredHandler();
        $this->namesHandler();
        $this->messageHandler();
    }

    public function registeredHandler(): void
    {
        $this->client->on('registered', function() {
            $this->console->info('connected');
        });
    }

    public function messageHandler(): void
    {
        $this->client->on('message', function (string $from, IrcChannel $channel, string $message) {
            $channelName = $channel->getName();
            $this->console->line("$channelName @$from: $message");
            
            # Record Downloadable Packets in the message.
            $packet = $this->packetLocator->locate($message, $from, $this->network, $this->channel);
            if (null !== $packet) {
                $this->console->info("{$packet->size} {$packet->file_name} [ /msg {$packet->bot->nick}: XDCC send #{$packet->number} ]");
            }
        });
    }

    public function pingHandler(): void
    {
        $this->client->on('ping', function() {
            $this->console->info('ping');
        });
    }

    public function namesHandler(): void
    {
        $this->client->on("names#{$this->channel->name}", function ($userList) {
            if (count($userList) > 0) {
                $rows = [];
                foreach($userList as $user) {
                    $rows[] = [$user];
                }
    
                $this->console->table(['Nick'], $rows);
            }
        });
    }

    public function connect()
    {
        return $this->client->connect();
    }

}
