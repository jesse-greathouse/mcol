<?php

namespace App\Jobs;

use App\Exceptions\InvalidClientException;
use App\Models\Bot;
use App\Models\Client;
use App\Models\Download;
use App\Models\FileDownloadLock;
use App\Models\Instance;
use App\Models\Operation;
use App\Packet\DownloadQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CancelRequest implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic.
     */
    public int $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 1;

    public function __construct(public Bot $bot) {}

    /**
     * Execute the job.
     *
     * This cancels the XDCC transfer and removes associated download data.
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->bot->nick} XDCC CANCEL";
        $client = $this->getClient();

        $instance = Instance::updateOrCreate(
            ['client_id' => $client->id],
            ['desired_status' => Instance::STATUS_UP]
        );

        $op = Operation::create([
            'instance_id' => $instance->id,
            'status' => Operation::STATUS_PENDING,
            'command' => $command,
        ]);

        if (! $op) {
            Log::error("Failed to cancel the XDCC transfer with: {$this->bot->nick}");
        } else {
            // Remove the file, lock, and Download Records for this bot.
            foreach ($this->getDownloadsForBot() as $download) {
                if (file_exists($download->file_uri)) {
                    unlink($download->file_uri);
                }
                $this->releaseLock($download);
                $download->delete();
            }
        }
    }

    /**
     * Get the client associated with the bot's network.
     *
     * @throws InvalidClientException if no client is found.
     */
    public function getClient(): ?Client
    {
        $client = Client::where('network_id', $this->bot->network->id)->first();
        if ($client === null) {
            throw new InvalidClientException("Client for network: {$this->bot->network->name} was not found.");
        }

        return $client;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) "{$this->bot->nick}-XDCC-CANCEL";
    }

    /**
     * Get the downloads associated with the bot.
     *
     * Optimized query to join tables for a more efficient result set.
     */
    protected function getDownloadsForBot(): Collection
    {
        return Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
            ->join('file_download_locks', 'file_download_locks.file_name', '=', 'packets.file_name')
            ->join('bots', 'bots.id', '=', 'packets.bot_id')
            ->where('bots.id', $this->bot->id)
            ->get(DownloadQueue::$columns);
    }

    /**
     * Release the download lock for the given download.
     */
    protected function releaseLock(Download $download): void
    {
        $fileName = basename($download->file_uri);
        $lock = FileDownloadLock::where('file_name', $fileName)->first();

        if ($lock !== null) {
            $lock->delete();
        } else {
            Log::warning("Attempted to remove download lock for file: $fileName, but lock did not exist.");
        }
    }
}
