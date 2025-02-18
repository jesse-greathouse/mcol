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
    /** @var string Target of the chat message (user or room) */
    protected string $target;

    /** @var Network|null Network instance used for connection */
    protected ?Network $network = null;

    /** @var string The formatted message to send */
    protected string $message;

    /** @var Nick|null The persona used for chatting */
    protected ?Nick $nick = null;

    /** @var string Console command signature */
    protected $signature = 'mcol:chat {network} {target} {message}';

    /** @var string Console command description */
    protected $description = 'Chat CLI Interface for mcol client instances.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $network = $this->getNetwork();
        $target = $this->getTarget();

        if (!$network || !$target) {
            $this->error('Invalid input.');
            return;
        }

        $nick = $this->getNickForNetwork();

        if (!$nick) {
            $this->error('Invalid nick. Cannot continue.');
            return;
        }

        $client = Client::updateOrCreate(
            ['network_id' => $network->id],
            ['nick_id' => $nick->id]
        );

        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        $operation = Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $this->getMessage(),
        ]);

        if (!$operation) {
            $this->error('Operation could not be completed.');
        }
    }

    /**
     * Formats the message to be issued in the operation.
     *
     * @param string $message
     * @return string
     */
    public function filterMessage(string $message): string
    {
        return "PRIVMSG {$this->getTarget()} :$message";
    }

    /**
     * Retrieves the Nick instance for the current network.
     *
     * @return Nick|null
     */
    public function getNickForNetwork(): ?Nick
    {
        if (!$this->nick) {
            $network = $this->getNetwork();
            $this->nick = $network ? Client::where('network_id', $network->id)->value('nick') : null;
        }
        return $this->nick;
    }

    /**
     * Retrieves the Network instance.
     *
     * @return Network|null
     */
    public function getNetwork(): ?Network
    {
        return $this->network ??= Network::where('name', $this->argument('network'))->first();
    }

    /**
     * Retrieves the chat message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message ??= $this->filterMessage($this->argument('message'));
    }

    /**
     * Retrieves the chat target (user or room).
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target ??= $this->argument('target');
    }
}
