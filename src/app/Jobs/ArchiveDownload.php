<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\Download,
    App\Models\DownloadHistory;

use \Exception;

class ArchiveDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * The Download object.
     *
     * @var Download
     */
    public $download;

    /**
     * @param Download $download
     */
    public function __construct(Download $download){
        $this->download = $download;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Variable shortcuts
            $download = $this->download;
            $packet = $download->packet;
            $channel = $packet->channel;
            $bot = $packet->bot;
            $network = $bot->network;
            $mediaType = (null !== $packet->media_type) ? $packet->media_type : 'unknown'; // Not null.

            DownloadHistory::create([
                'file_name'         => basename($packet->file_name),
                'media_type'        => basename($mediaType),
                'file_uri'          => $download->file_uri,
                'bot_nick'          => $bot->nick,
                'network_name'      => $network->name,
                'channel_name'      => $channel->name,
                'file_size_bytes'   => $download->file_size_bytes
            ]);

            $download->delete();
        } catch(Exception $e) {
            Log::warning($e->getMessage());
            $this->fail($e);
        }
    }
}
