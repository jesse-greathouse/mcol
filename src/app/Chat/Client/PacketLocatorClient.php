<?php

namespace App\Chat\Client;

use Illuminate\Console\Command,
    Illuminate\Database\QueryException;


use JesseGreathouse\PhpIrcClient\IrcClient,
    JesseGreathouse\PhpIrcClient\IrcChannel;

use App\Exceptions\NetworkWithNoChannelException,
    App\Chat\Client,
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
                $c = $this->getChannelByName($channel->getName());
                // Only record packet #'s if this is a parent channel.
                if (null !== $c && null === $c->parent) {
                    $bot = $this->getBotByNick($from);
                    $errorMessage = "Error parsing packet message: \"$message\"";
                    try {
                        Parse::packet($message, $bot, $c, $this->cache);
                    } catch(QueryException $e) {
                        $this->console->error($errorMessage);
                        $this->console->error($e->getMessage());
                    }  catch(NetworkWithNoChannelException $e) {
                        $this->console->error($errorMessage);
                        $this->console->error($e->getMessage());
                    }
                }
            }
        });

        parent::messageHandler();
    }
}
