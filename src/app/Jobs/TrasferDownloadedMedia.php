<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Exceptions\TransferDownloadFileNotFoundException,
    App\Media\TransferManager,
    App\Media\Service\Plex,
    App\Models\DownloadDestination;

use \Exception;

class TrasferDownloadedMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The Download object.
     *
     * @var DownloadDestination
     */
    public $downloadDestination;

    /**
     * Create a new job instance.
     */
    public function __construct(DownloadDestination $downloadDestination)
    {
        $this->downloadDestination = $downloadDestination;
    }

    /**
     * Execute the job.
     */
    public function handle(Plex $plex): void
    {
        $download = $this->downloadDestination->download;
        if (file_exists($download->file_uri)) {
            $this->downloadDestination->status = DownloadDestination::STATUS_INCOMPLETE;
            $this->downloadDestination->save();

            $varDir = env('VAR', '/var/mcol');
            $tmpDir = "$varDir/transfer";
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir);
            }
            $options = ['tmp_dir' => $tmpDir];

            try {
                $manager = new TransferManager($download->file_uri, $this->downloadDestination->destination_dir, $options);
                $manager->transfer();
                $this->downloadDestination->status = DownloadDestination::STATUS_COMPLETED;
                $this->downloadDestination->save();

                // Do Plex Media Scan if Plex is configured.
                if ($plex->isConfigured()) {
                    $type = $download->packet->media_type;
                    if (in_array($type, $plex->getEnabledMediaTypes())) {
                        $plex->scanMediaLibrary($type);
                    }
                }
            } catch(Exception $e) {
                $this->fail($e);
                Log::warning($e);
            }
        } else {
            // file Disappeared.
            $e = new TransferDownloadFileNotFoundException("Job attempted to transfer: \"{$download->file_uri}\" but file no longer exists.");
            $this->fail($e);
            Log::warning($e->getMessage());
        }
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->downloadDestination->id;
    }
}
