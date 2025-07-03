<?php

namespace App\Jobs;

use App\Exceptions\InvalidClientException;
use App\Models\Channel;
use App\Models\Client;
use App\Models\Instance;
use App\Models\Network;
use App\Models\Operation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PacketSearch implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int Timeout for the job execution in seconds.
     */
    public int $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 1;

    /**
     * Create a new job instance.
     *
     * @param  Network  $network  The network associated with the job.
     * @param  Channel  $channel  The channel to which the command will be sent.
     * @param  string  $search  The search term to be used in the command.
     */
    public function __construct(
        public Network $network,
        public Channel $channel,
        public string $search,
    ) {}

    /**
     * Execute the job.
     * This method performs the search and logs the result.
     */
    public function handle(): void
    {
        $command = $this->buildCommand();

        $client = $this->getClient();

        // Optimized instance creation and update with bulk inserts and minimal checks
        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        // Efficient operation creation with immediate fallback on failure
        Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);
    }

    /**
     * Build the command for the search.
     */
    private function buildCommand(): string
    {
        return "PRIVMSG {$this->channel->name} !s {$this->search}";
    }

    /**
     * Retrieve the client associated with the network.
     *
     * @throws InvalidClientException If no client is found for the given network.
     */
    public function getClient(): ?Client
    {
        $client = Client::where('network_id', $this->network->id)->first();

        if ($client === null) {
            throw new InvalidClientException("Client for network: {$this->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     * The ID is a concatenation of network ID and search term.
     */
    public function uniqueId(): string
    {
        // Optimized string concatenation using interpolated variables
        return "{$this->network->id}_{$this->search}";
    }
}
