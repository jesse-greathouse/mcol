<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldBeUnique,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Exceptions\InvalidClientException,
    App\Models\Bot,
    App\Models\Client,
    App\Models\Instance,
    App\Models\Operation;

/**
 * Handles the request for the bot's XDCC list and updates the associated instance and operation.
 */
class BotListRequest implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out. Default is 3 seconds.
     * This is subject to change in the future to be dynamic based on job type or configuration.
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 1;

    /**
     * The bot instance associated with this job.
     *
     * @var Bot
     */
    public function __construct(public Bot $bot) {}

    /**
     * Executes the job to request the XDCC list and log the operation status.
     * It also ensures the client and associated instance are updated.
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->bot->nick} XDCC SEND LIST";
        $client = $this->getClient();

        // Update or create the instance with the given client ID
        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        // Create a new operation with the instance ID and status
        $op = Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);

        // Log an error if the operation creation fails
        if (!$op) {
            Log::error("Failed to request XDCC list for: {$this->bot->nick}");
        }
    }

    /**
     * Retrieves the client associated with the bot's network.
     *
     * @throws InvalidClientException If no client is found for the bot's network.
     *
     * @return Client|null The associated client or null if not found.
     */
    public function getClient(): Client|null
    {
        $client = Client::where('network_id', $this->bot->network->id)->first();

        if (null === $client) {
            throw new InvalidClientException("Client for network: {$this->bot->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Gets the unique ID for the job, which is a combination of the bot's nickname and command.
     *
     * @return string The unique job ID.
     */
    public function uniqueId(): string
    {
        return (string) "{$this->bot->nick}-XDCC-LIST";
    }
}
