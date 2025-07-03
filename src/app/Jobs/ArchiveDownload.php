<?php

namespace App\Jobs;

use App\Models\Download;
use App\Models\DownloadHistory;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class ArchiveDownload
 *
 * Handles the archiving process for a download, including creating history records and deleting the original download.
 */
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
     * @var \App\Models\Download
     */
    public $download;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Download  $download  The download instance to archive.
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * Execute the job to archive the download and create history.
     */
    public function handle(): void
    {
        try {
            // Shortcut variables for improved readability and reduced property lookups.
            $download = $this->download;
            $packet = $download->packet;
            $channel = $packet->channel;
            $bot = $packet->bot;
            $network = $bot->network;
            $mediaType = $download->media_type ?? 'unknown'; // Use null coalescing for default.

            // Create a history record for the download.
            DownloadHistory::create([
                'file_name' => $download->file_name,
                'media_type' => $mediaType,
                'file_uri' => $download->file_uri,
                'bot_nick' => $bot->nick,
                'network_name' => $network->name,
                'channel_name' => $channel->name,
                'file_size_bytes' => $download->file_size_bytes,
                'meta' => $download->meta,
            ]);

            // Delete the original download after processing.
            $download->delete();
        } catch (Exception $e) {
            // Log and fail the job if an exception occurs.
            Log::warning($e->getMessage());
            $this->fail($e);
        }
    }
}
