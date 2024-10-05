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
    App\Jobs\CheckFileDownloadScheduled,
    App\Models\Client,
    App\Models\FileDownloadLock,
    App\Models\Instance,
    App\Models\Operation,
    App\Models\Packet;

use \DateTime;

class DownloadRequest implements ShouldQueue, ShouldBeUnique
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

    public function __construct(public Packet $packet){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->isFileDownloadLocked()) {
            $this->fail("The file: {$this->packet->file_name} is locked for Downloading.");
            return;
        }

        $command = "PRIVMSG {$this->packet->bot->nick} XDCC SEND {$this->packet->number}";
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
            Log::error("Failed to queue for download of packet: {$this->packet->id} file: {$this->packet->file_name}");
        } else {
            // Lock the file for Downloading to prevent further downloads of the same file.
            FileDownloadLock::create(['file_name' => $this->packet->file_name]);

            //Queue the job that checks if the bot scheduled the download.
            $timeStamp = new DateTime('now');
            CheckFileDownloadScheduled::dispatch($this->packet->file_name, $timeStamp)
                ->delay(now()->addMinutes(CheckFileDownloadScheduled::SCHEDULE_INTERVAL));
        }
    }

    public function getClient(): Client|null
    {
        $client = Client::where('network_id', $this->packet->network->id)->first();
        if (null === $client) {
            throw new InvalidClientException("Client for network: {$this->packet->network->name} was not found.");
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
     * Checks to see if there is a download lock on the file name.
     * Download locks prevents a file from being simultanously downloaded from multiple sources.
     *
     * @return boolean
     */
    public function isFileDownloadLocked(): bool
    {
        $lock = FileDownloadLock::where('file_name', $this->packet->file_name)->first();

        if (null !== $lock) {
            return true;
        } else {
            return false;
        }
    }
}
