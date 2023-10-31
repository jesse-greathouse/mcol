<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Client,
    App\Models\Instance,
    App\Models\Nick,
    App\Models\Operation,
    App\Models\Network;

class Chat extends Command
{

    const COMMAND_MASK = '/^\/msg\s/is';

    /**
     * Target of : `PRIVMSG {target} {message}`
     *
     * @var string
     */
    protected $target;

    /**
     * 
     * Network from which we can connect to an instance.
     *
     * @var string
     */
    protected $network;

     /**
     * 
     * The text to pass to the chat client.
     *
     * @var string
     */
    protected $message;

    /**
     * 
     * The Nick as the persona whom is chatting.
     *
     * @var Nick
     */
    protected $nick;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:chat {network} {target} {message}'; # Target can be a room or a user.

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chat CLI Interface for mcol client instances.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $network = $this->getNetwork();
        $target = $this->getTarget();

        if (!$network || !$target) {
            $this->error('invalid input.');
            exit(0);
        }

        $nick = $this->getNickForNetwork();

        if (!$nick) {
            $this->error('invalid nick. Cannot continue.');
            exit(0);
        }

        $command = $this->getMessage();

        $client = Client::updateOrCreate(
            ['network_id' => $network->id],
            ['nick_id' => $nick->id]
        );

        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP ]
        );

        $op = Operation::create(
            [
                'instance_id' => $instance->id, 
                'command' => $this->message, 
                'status' => Operation::STATUS_PENDING, 
                'command' => $command,
            ]
        );

        if (!$op) {
            $this->error('Operation could not be completed.');
            exit(0);
        }

    }

    /**
     * Filters the message to be issued in the operation.
     *
     * @param string $message
     * @return string
     */
    public function filterMessage(string $message): string
    {
        return "PRIVMSG {$this->target} :$message";
    }

    public function getNickForNetwork(): Nick|null
    {

        if (null === $this->nick) {
            $network = $this->getNetwork();
            if (null === $network) {
                return null;
            }

            $client = Client::where('network_id', $network->id)->first();
            if (null === $client) {
                return null;
            }

            $this->nick = $client->nick;
            
        }

        return $this->nick;

    }

    public function getNetwork(): Network
    {
        if (null === $this->network) {
            $networkName = $this->argument('network');
            $n = Network::where('name', $networkName)->first();
            if (!$n) {
                return null;
            }

            $this->network = $n;
        }

        return $this->network;
    }

    public function getInstanceFrom(): Network
    {
        if (null === $this->network) {
            $networkName = $this->argument('network');
            $n = Network::where('name', $networkName)->first();
            if (!$n) {
                return null;
            }

            $this->network = $n;
        }

        return $this->network;
    }

    public function getMessage(): string
    {
        if (null === $this->message) { 
            $this->message = $this->filterMessage($this->argument('message'));
        }

        return $this->message;
    }

    public function getTarget(): string
    {
        if (null === $this->target) { 
            $this->target = $this->argument('target');;
        }

        return $this->target;
    }
}
