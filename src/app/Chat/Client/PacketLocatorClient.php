<?php

namespace App\Chat\Client;

use Illuminate\Console\Command,
    Illuminate\Database\QueryException;


use JesseGreathouse\PhpIrcClient\IrcClient,
    JesseGreathouse\PhpIrcClient\IrcChannel;

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
     * An lookup table of instantiated Bot Models associated with this client.
     * Keeps instantiated bots in memory so we don't have to keep hitting the DB.
     *
     * @var array<string, Bot>
     */
    protected array $bots = [];

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
                $c = $this->getChannelFromName($channel->getName());
                // Only record packet #'s if this is a parent channel.
                if (null !== $c && null === $c->parent) {
                    $bot = $this->getBotFromNick($from);
                    try {
                        Parse::packet($message, $bot, $c, $this->cache);
                    } catch(QueryException $e) {
                        $this->console->error("Error parsing packet message: \"$message\"");
                        $this->console->error($e->getMessage());
                    }
                }
            }
        });

        parent::messageHandler();
    }

    /**
     * Returns a Bot model object with the parameter of the bot nick.
     *
     * @param string $nick
     * @return Bot
     */
    public function getBotFromNick(string $nick): Bot
    {
        if (!isset($this->bots[$nick])) {
            $this->bots[$nick] = Bot::updateOrCreate(
                [ 'network_id' => $this->network->id, 'nick' => $nick ]
            );
        }

        return $this->bots[$nick];
    }

}
