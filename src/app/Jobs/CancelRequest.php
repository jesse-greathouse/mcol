<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    public function __construct(public Bot $bot){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $command = "PRIVMSG {$this->bot->nick} XDCC CANCEL";
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
            Log::error("Failed to cancel the XDCC transwer with: {$this->bot->nick}");
        } else {
            // Remove the file, lock and the Download Records for this bot.
            foreach($this->getDownloadsForBot() as $download) {
                if (file_exists($download->file_uri)) {
                    unlink($download->file_uri);
                }
                $this->releaseLock($download);
                $download->delete();
            }
        }
    }

    public function getClient(): Client|null
    {
        $client = Client::where('network_id', $this->bot->network->id)->first();
        if (null === $client) {
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
     * Undocumented function
     *
     * @return Collection
     */
    protected function getDownloadsForBot(): Collection
    {
        return Download::join('packets', 'packets.id', '=', 'downloads.packet_id')
            ->join ('file_download_locks', 'file_download_locks.file_name', 'packets.file_name')
            ->join ('bots', 'bots.id', 'packets.bot_id')
            ->where('bots.id', $this->bot->id)
            ->get(DownloadQueue::$columns);
    }

    /**
     * Returns a single Download model instance.
     *
     * @param Download $download
     * @return void
     */
    protected function releaseLock(Download $download): void
    {
        $fileName = basename($download->file_uri);
        $lock = FileDownloadLock::where('file_name', $fileName)->first();
        if (null !== $lock) {
            $lock->delete();
        } else {
            Log::warning("Attempted download lock removal of: $fileName, failed. Lock did not exist.");
        }
    }
}
