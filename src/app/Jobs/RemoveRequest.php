<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldBeUnique,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Database\Eloquent\ModelNotFoundException,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Exceptions\InvalidClientException,
    App\Models\Packet,
    App\Models\Client,
    App\Models\Download,
    App\Models\FileDownloadLock,
    App\Models\Instance,
    App\Models\Operation;

class RemoveRequest implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic
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
     * The packet instance associated with this job.
     *
     * @var Packet
     */
    public function __construct(public Packet $packet) {}

    /**
     * Execute the job to remove the request and manage downloads.
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->packet->bot->nick} XDCC REMOVE {$this->packet->number}";
        $client = $this->getClient();

        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);

        $this->removeDownloads();
    }

    /**
     * Retrieve the client associated with the packet's network.
     *
     * @return Client|null
     * @throws InvalidClientException
     */
    public function getClient(): Client|null
    {
        try {
            $client = Client::where('network_id', $this->packet->bot->network->id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new InvalidClientException("Client for network: {$this->packet->bot->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) "{$this->packet->id}-XDCC-REMOVE";
    }

    /**
     * Removes queued Downloads associated with this packet.
     *
     * @return void
     */
    protected function removeDownloads(): void
    {
        Download::where('packet_id', $this->packet->id)
            ->each(function ($download) {
                $this->releaseLock($download);
                $download->delete();
            });
    }

    /**
     * Removes any lock from a specific download.
     *
     * @param Download $download
     * @return void
     */
    protected function releaseLock(Download $download): void
    {
        FileDownloadLock::where('file_name', basename($download->file_uri))->delete();
    }
}
