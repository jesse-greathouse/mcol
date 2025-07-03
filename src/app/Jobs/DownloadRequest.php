<?php

namespace App\Jobs;

use App\Exceptions\InvalidClientException;
use App\Models\Client;
use App\Models\Download;
use App\Models\FileDownloadLock;
use App\Models\Instance;
use App\Models\Operation;
use App\Models\Packet;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class DownloadRequest
 *
 * Handles the downloading request process, checking locks, archiving, and queuing operations.
 */
class DownloadRequest implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic.
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
     * The packet associated with the download request.
     *
     * @var Packet
     */
    public function __construct(public Packet $packet) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->isFileDownloadLocked()) {
            $this->fail("The file: {$this->packet->file_name} is locked for downloading.");

            return;
        }

        // Archive any download records associated with this packet.
        $this->archiveDownloads();

        // Create the operation for the download using the helper method, which handles client and instance creation.
        $this->createOperation();

        // Lock the file for downloading to prevent further downloads of the same file.
        FileDownloadLock::create(['file_name' => $this->packet->file_name]);

        // Queue the job to check if the bot scheduled the download.
        $timeStamp = new DateTime('now');
        CheckFileDownloadScheduled::dispatch($this->packet->file_name, $timeStamp)
            ->delay(now()->addMinutes(CheckFileDownloadScheduled::SCHEDULE_INTERVAL));
    }

    /**
     * Helper method to create the operation for the download, including client and instance creation.
     */
    protected function createOperation(): void
    {
        // Get the client associated with the packet's network.
        $client = $this->getClient();

        // Create or update the instance for the client.
        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        // Construct the command string for the operation.
        $command = "PRIVMSG {$this->packet->bot->nick} XDCC SEND {$this->packet->number}";

        // Create the operation for the download.
        Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);
    }

    /**
     * Get the client associated with the packet's network.
     *
     * @throws InvalidClientException
     */
    public function getClient(): ?Client
    {
        $client = Client::where('network_id', $this->packet->network->id)->first();

        if ($client === null) {
            throw new InvalidClientException("Client for network: {$this->packet->network->name} not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->packet->id;
    }

    /**
     * Removes queued downloads associated with this packet.
     */
    protected function archiveDownloads(): void
    {
        $downloads = Download::where('packet_id', $this->packet->id)->get();

        // Dispatch archive download jobs for all associated downloads.
        foreach ($downloads as $download) {
            ArchiveDownload::dispatch($download);
        }
    }

    /**
     * Checks if there is a download lock on the file.
     * Download locks prevent a file from being simultaneously downloaded from multiple sources.
     */
    public function isFileDownloadLocked(): bool
    {
        // Return whether the lock exists for the file name.
        return FileDownloadLock::where('file_name', $this->packet->file_name)->exists();
    }
}
