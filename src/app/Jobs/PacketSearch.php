<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
     * @var int
     */
    public $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 1;

    public function __construct(
        public Network $network,
        public Channel $channel, 
        public string $search,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->channel->name} :!s {$this->search}";
        $client = $this->getClient();

        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP ]
        );

        $op = Operation::create(
            [
                'instance_id' => $instance->id,
                'status' => Operation::STATUS_PENDING, 
                'command' => $command,
            ]
        );

        if (!$op) {
            Log::error("Failed to commit the search: {$this->search}");
        }
    }

    public function getClient(): Client|null
    {
        $client = Client::where('network_id', $this->network->id)->first();
        if (null === $client) {
            throw new InvalidClientException("Client for network: {$this->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "{$this->network->id}_{$this->network->id}_$this->search";
    }
}
