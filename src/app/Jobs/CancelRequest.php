<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldBeUnique,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Database\Eloquent\Collection,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Exceptions\InvalidClientException,
    App\Models\Bot,
    App\Models\Client,
    App\Models\Download,
    App\Models\FileDownloadLock,
    App\Models\Instance,
    App\Models\Operation,
    App\Packet\DownloadQueue;

class CancelRequest implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic.
     *
     * @var int
     */
    public int $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 1;

    public function __construct(public Bot $bot)
    {}

    /**
     * Execute the job.
     *
     * This cancels the XDCC transfer and removes associated download data.
     *
     * @return void
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

        if (!$op) {
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
     * @return Client|null
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
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return (string) "{$this->bot->nick}-XDCC-CANCEL";
    }

    /**
     * Get the downloads associated with the bot.
     *
     * Optimized query to join tables for a more efficient result set.
     *
     * @return Collection
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
     *
     * @param Download $download
     * @return void
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
