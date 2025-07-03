<?php

namespace App\Chat;

use App\Models\Instance;
use App\Models\Operation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JesseGreathouse\PhpIrcClient\IrcClient;

class OperationManager
{
    // https://www.phpliveregex.com/p/MDc
    const COMMAND_PRIVMSG_MASK = '/PRIVMSG (\S+) (.*)$/is';

    /**
     * Client of chat client
     *
     * @var IrcClient
     */
    protected $client;

    /**
     * Instance of chat client
     *
     * @var Instance
     */
    protected $instance;

    /**
     * Console for using this client
     *
     * @var Command
     */
    protected $console;

    public function __construct(IrcClient $client, Instance $instance, Command $console)
    {
        $this->client = $client;
        $this->instance = $instance;
        $this->console = $console;
    }

    public function doOperations(): void
    {
        $operation = Operation::where('instance_id', $this->instance->id)
            ->where('status', Operation::STATUS_PENDING)->first();

        if ($operation !== null) {
            $status = Operation::STATUS_COMPLETED;

            try {
                $this->execute($operation->command);
            } catch (\Exception $e) {
                $status = Operation::STATUS_FAILED;
                $this->console->error($e->getMessage());
                Log::error($e);
            }

            $operation->status = $status;
            $operation->save();
        }
    }

    /**
     * Execute a command.
     */
    protected function execute(string $command): void
    {
        $privMsgMatch = [];
        preg_match(self::COMMAND_PRIVMSG_MASK, $command, $privMsgMatch);

        // If the command is a PRIVMSG, use the say command in the client.
        // say sas predefined guardrails for sending messages.
        if (count($privMsgMatch) > 2) {
            [, $target, $command] = $privMsgMatch;
            $this->say($target, $command);

            return;
        }

        $this->console->info(sprintf('[%s]: %s > %s',
            $this->instance->client->network->name,
            $this->instance->client->nick->nick,
            $command
        ));

        $this->client->send($command);

    }

    /**
     * Does a command via the Client::say() method.
     * Has predefined guardrails for sending messages.
     */
    protected function say(string $target, string $command): void
    {
        $this->console->info(sprintf('[%s]: %s > %s',
            $this->instance->client->network->name,
            $this->instance->client->nick->nick,
            "/msg $target $command"
        ));

        $this->client->say($target, $command);

    }
}
