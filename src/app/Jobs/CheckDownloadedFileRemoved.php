<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Jobs\TrasferDownloadedMedia,
    App\Models\Download,
    App\Models\DownloadDestination,
    App\Models\FileDownloadLock;

/**
 * Class CheckDownloadedFileRemoved
 *
 * Handles checking whether a downloaded file has been removed and performing
 * actions like removing locks, archiving downloads, and handling download destinations.
 */
class CheckDownloadedFileRemoved implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Interval in minutes to reschedule the job */
    const SCHEDULE_INTERVAL = 3;

    /** @var int The number of seconds the job can run before timing out */
    public $timeout = 3;

    /** @var Download The associated Download object */
    public $download;

    /** @var \DateTime Timestamp for the cutoff of the lookup */
    public $timeStamp;

    /**
     * CheckDownloadedFileRemoved constructor.
     *
     * @param Download $download
     */
    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    /**
     * Execute the job.
     *
     * Checks if the downloaded file has been removed and performs relevant actions.
     * If the file is missing, it deletes any associated download lock and dispatches
     * an archiving job. Otherwise, it handles the download destination and reschedules.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!file_exists($this->download->file_uri)) {
            $this->removeDownloadLock();
            ArchiveDownload::dispatch($this->download);
        } else {
            $this->handleDownloadDestination();

            // Reschedule this job at the specified interval.
            self::dispatch($this->download)
                ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
        }
    }

    /**
     * Remove the download lock for the given file.
     *
     * Attempts to remove the lock from the FileDownloadLock model.
     * Logs a warning if no lock is found.
     *
     * @return void
     */
    protected function removeDownloadLock(): void
    {
        $fileName = basename($this->download->file_uri);
        $lock = FileDownloadLock::where('file_name', $fileName)->first();

        if ($lock) {
            $lock->delete();
        } else {
            Log::warning("Attempted download lock removal of: $fileName, failed. Lock did not exist.");
        }
    }

    /**
     * Handles transferring a file that has a destination registered.
     *
     * Checks if a download destination is registered for this download.
     * If found, it updates the status and dispatches a transfer job.
     *
     * @return void
     */
    protected function handleDownloadDestination(): void
    {
        // Retrieve the first download destination with waiting status.
        $downloadDestination = DownloadDestination::where('download_id', $this->download->id)
            ->where('status', DownloadDestination::STATUS_WAITING)
            ->first();

        if ($downloadDestination) {
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();
            TrasferDownloadedMedia::dispatch($downloadDestination)->onQueue('transfer');
        }
    }
}
