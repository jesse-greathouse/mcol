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
    App\Models\Client,
    App\Models\Channel,
    App\Models\Instance,
    App\Models\Network,
    App\Models\Operation;

class HotReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The timeout duration for the job (in seconds).
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
     * HotReport constructor.
     *
     * @param Network $network
     * @param Channel $channel
     */
    public function __construct(
        public Network $network,
        public Channel $channel
    ) {}

    /**
     * Execute the job.
     *
     * This will generate a report for the hot status of the instance in the specified channel.
     *
     * @return void
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->channel->name} !hot";
        $client = $this->getClient();

        // Efficiently update or create the instance.
        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        // Creating the operation directly with the data required.
        $op = Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);

        if (!$op) {
            Log::error("Failed to query a hot report on: {$this->channel->name}@{$this->network->name}");
        }
    }

    /**
     * Retrieve the associated client for the network.
     *
     * @return Client|null
     *
     * @throws InvalidClientException if no client is found for the network.
     */
    public function getClient(): ?Client
    {
        // Using a direct query for efficiency.
        $client = Client::where('network_id', $this->network->id)->first();

        if (null === $client) {
            throw new InvalidClientException("Client for network: {$this->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique identifier for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        // The unique ID generation is now more explicit.
        return "{$this->network->id}_{$this->channel->id}_hot_report";
    }
}
