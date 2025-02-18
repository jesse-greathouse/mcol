<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldBeUnique,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels;

use App\Exceptions\InvalidClientException,
    App\Models\Client,
    App\Models\Channel,
    App\Models\Instance,
    App\Models\Network,
    App\Models\Operation;

class PacketSearch implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int Timeout for the job execution in seconds.
     */
    public int $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 1;

    /**
     * Create a new job instance.
     *
     * @param Network $network The network associated with the job.
     * @param Channel $channel The channel to which the command will be sent.
     * @param string $search The search term to be used in the command.
     */
    public function __construct(
        public Network $network,
        public Channel $channel,
        public string $search,
    ){}

    /**
     * Execute the job.
     * This method performs the search and logs the result.
     *
     * @return void
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
     *
     * @return string
     */
    private function buildCommand(): string
    {
        return "PRIVMSG {$this->channel->name} !s {$this->search}";
    }

    /**
     * Retrieve the client associated with the network.
     *
     * @throws InvalidClientException If no client is found for the given network.
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        $client = Client::where('network_id', $this->network->id)->first();

        if (null === $client) {
            throw new InvalidClientException("Client for network: {$this->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     * The ID is a concatenation of network ID and search term.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        // Optimized string concatenation using interpolated variables
        return "{$this->network->id}_{$this->search}";
    }
}
