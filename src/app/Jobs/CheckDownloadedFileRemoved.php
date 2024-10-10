<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\Download,
    App\Models\FileDownloadLock;

class CheckDownloadedFileRemoved implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const SCHEDULE_INTERVAL = 3;

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
     * Timestamp for the cutoff of the lookup.
     *
     * @var DateTime
     */
    public $timeStamp;

    public function __construct(Download $download){
        $this->download = $download;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!file_exists($this->download->file_uri)) {
            $fileName = basename($this->download->file_uri);
            $lock = FileDownloadLock::where('file_name', $fileName)->first();
            if (null !== $lock) {
                $lock->delete();
            } else {
                Log::warning("Attempted download lock removal of: $fileName, failed. Lock did not exist.");
            }
            // Move the download to the archives table
            ArchiveDownload::dispatch($this->download);
        } else {
            // Reschedule this job at the specified interval.
            self::dispatch($this->download)
                ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
        }
    }
}
