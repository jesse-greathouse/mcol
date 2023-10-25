<?php

namespace App\Chat;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel,
    Jerodev\PhpIrcClient\Options\ClientOptions;

use App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

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
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    /**
     * ChannelUpdater instance for this client.
     *
     * @var ChannelUpdater
     */
    protected $channelUpdater;

    /**
     * IRC client
     * 
     * @var IrcClient
     */
    protected $client;

    public function __construct(Nick $nick, Network $network, Command $console) {
        $this->nick = $nick;
        $this->network = $network;
        $this->console = $console;
        $this->channelUpdater = new ChannelUpdater();

        $options = new ClientOptions($nick->nick);

        $this->client = new IrcClient("{$network->firstServer->host}:6667", $options);
        $this->assignHandlers();
    }

    /**
     * Assigns all handlers for the various events.
     *
     * @return void
     */
    protected function assignHandlers(): void
    {
        $this->registeredHandler();
        $this->pingHandler();
        $this->namesHandler();
        $this->messageHandler();
        $this->privMessageHandler();
        $this->kickHandler();
    }

    /**
     * Handles a kick event.
     *
     * @return void
     */
    public function kickHandler(): void
    {
        $this->client->on('kick', function(IrcChannel $channel, string $user, string $kicker, $message) {
            $channelName = $channel->getName();
            $this->console->error("$user has been kicked from $channelName by $kicker. reason:\n$message\n\n");

            # Update the Channel Metadata
            $this->channelUpdater->update($channel);
        });
    }

    /**
     * Handles the registered event (connected).
     *
     * @return void
     */
    public function registeredHandler(): void
    {
        $this->client->on('registered', function() {
            $this->console->info('connected');

            $channels = $this->getAllParentChannelsForNetwork($this->network);

            foreach($channels as $channel) {
                $this->client->join($channel->name);
                foreach($channel->children as $child) {
                    $this->client->join($child->name);
                }
            }  
        });
    }

    /**
     * Handles Private Messages
     *
     * @return void
     */
    public function privMessageHandler(): void
    {
        $this->client->on('privmsg', function (string $userName, $target, string $message) {
            $this->console->warn("$userName to $target says: $message");
        });
    }

    /**
     * Handles standard messages in channel.
     *
     * @return void
     */
    public function messageHandler(): void
    {
        $this->client->on('message', function (string $from, IrcChannel $channel, string $message) {
            $this->console->line($channel->getName() . " @$from: $message");

            # Update the Channel Metadata
            $this->channelUpdater->update($channel);
        });
    }

    /**
     * Handles Ping events.
     *
     * @return void
     */
    public function pingHandler(): void
    {
        $this->client->on('ping', function() {
            $this->console->info('ping');
        });
    }

    /**
     * Handles name events.
     *
     * @return void
     */
    public function namesHandler(): void
    {
        $this->client->on("names", function (IrcChannel $channel) {
            $userList = $channel->getUsers();
            if (count($userList) > 0) {
                $rows = [];
                foreach($userList as $user) {
                    $rows[] = [$user];
                }
    
                $this->console->table(['Nick'], $rows);
            }
        });
    }

    /**
     * Connect's to the Server and initializes event listening.
     *
     * @return void
     */
    public function connect(): void
    {
        $this->client->connect();
    }

    /**
     * Returns a channel model object with the parameter of the channel name.
     *
     * @param string $name
     * @return Channel
     */
    protected function getChannelFromName(string $name): Channel
    {
        return Channel::where('name', $name)->first();
    }

    protected function getAllParentChannelsForNetwork(NetWork $network): Collection
    {
        return Channel::where('channel_id', null)
            ->where('network_id', $network->id)
            ->get();
    }

}
