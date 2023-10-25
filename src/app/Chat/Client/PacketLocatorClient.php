<?php

namespace App\Chat\Client;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel;

use App\Chat\Client,
    App\Chat\PacketLocator,
    App\Models\Nick,
    App\Models\Network,
    App\Models\Channel;

class PacketLocatorClient extends Client
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

    public function __construct(Nick $nick, Network $network, Command $console) {
        $this->packetLocator = new PacketLocator();
        parent::__construct($nick, $network, $console);
    }

    /**
     * Handles standard messages in channel.
     *
     * @return void
     */
    public function messageHandler(): void
    {
        $this->client->on('message', function (string $from, IrcChannel $channel = null, string $message) {
            # Record downloadable packet #'s in the message.
            # Only record packet #'s if this is a parent channel.
            $c = $this->getChannelFromName($channel->getName());
            if (null !== $channel && null === $c->parent) {
                $packet = $this->packetLocator->locate($message, $from, $this->network, $c);
                if (null !== $packet) {
                    $this->console->info("{$packet->size} {$packet->file_name} [ /msg {$packet->bot->nick}: XDCC send #{$packet->number} ]");
                }
            }
        });

        parent::messageHandler();
    }

}
