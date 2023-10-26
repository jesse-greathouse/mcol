<?php

namespace App\Chat;

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

    public function __construct(IrcClient $client, Instance $instance)
    {
        $this->client = $client;
        $this->instance = $instance;
    }

    public function doOperations(): void
    {
        $operation = Operation::where('instance_id', $this->instance->id)
                        ->where('status', Operation::STATUS_PENDING)->first();

        if (null !== $operation) {
            $status = Operation::STATUS_COMPLETED;

            try {
                $this->client->send($operation->command);
            } catch (\Exception $e) {
                $status = Operation::STATUS_FAILED;
            }

            $operation->status = $status;
            $operation->save();
        }
    }

}
