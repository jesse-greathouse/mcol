<?php

namespace App\Chat\Client;

use Illuminate\Console\Command;

use Jerodev\PhpIrcClient\IrcClient,
    Jerodev\PhpIrcClient\IrcChannel;

use App\Chat\Client,
    App\Models\Bot,
    App\Models\Nick,
    App\Models\Network,
    App\Packet\Parse;

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
     * IRC client
     * 
     * @var IrcClient
     */
    protected $client;

    public function __construct(Nick $nick, Network $network, Command $console) {
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
            // Record downloadable packet #'s in the message.
            if (null !== $channel) {
                $c = $this->getChannelFromName($channel->getName());
                // Only record packet #'s if this is a parent channel.
                if (null !== $c && null === $c->parent) {

                    $bot = Bot::updateOrCreate(
                        [ 'network_id' => $this->network->id, 'nick' => $from ]
                    );

                    Parse::packet($message, $bot, $c);
                }
            }
        });

        parent::messageHandler();
    }

}
