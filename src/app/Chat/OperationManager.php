<?php

namespace App\Chat;

use Illuminate\Console\Command,
    Illuminate\Support\Facades\Log;

use Jerodev\PhpIrcClient\IrcClient;

use App\Models\Instance,
    App\Models\Operation;

class OperationManager
{

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

        if (null !== $operation) {
            $status = Operation::STATUS_COMPLETED;

            try {
                $this->console->info(sprintf("[%s]: %s > %s",
                    $this->instance->client->network->name,
                    $this->instance->client->nick->nick,
                    $operation->command
                ));
                $this->client->send($operation->command);
            } catch (\Exception $e) {
                $status = Operation::STATUS_FAILED;
                $this->console->error($e->getMessage());
                Log::error($e);
            }

            $operation->status = $status;
            $operation->save();
        }
    }
}
