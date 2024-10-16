<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Jobs\ArchiveDownload,
    App\Jobs\CheckDownloadedFileRemoved,
    App\Jobs\TrasferDownloadedMedia,
    App\Models\Download,
    App\Models\DownloadDestination,
    App\Models\FileDownloadLock,
    App\Packet\DownloadQueue;

use \DateTime;

class CheckFileDownloadCompleted implements ShouldQueue
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
     * Name of the file
     *
     * @var string
     */
    public $fileName;

    /**
     * The Download Queue querying tool.
     *
     * @var DownloadQueue
     */
    public $downloadQueue;

    /**
     * Timestamp for the cutoff of the lookup.
     *
     * @var DateTime
     */
    public $timeStamp;

    public function __construct(string $fileName, DateTime $timeStamp){
        $this->fileName = $fileName;
        $this->timeStamp = $timeStamp;
        $this->downloadQueue = new DownloadQueue();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $download = $this->getDownload();
        if (null === $download) {
            // File was not found in the Download queue, release the lock and warn.
            Log::warning("Expected: {$this->fileName}, in download queue but no record found.");
            $lock = FileDownloadLock::where('file_name', $this->fileName)->first();
            if (null !== $lock) {
                $lock->delete();
            } else {
                Log::warning("Attempted download lock removal of: {$this->fileName}, failed. Lock did not exist.");
            }
        } else if (Download::STATUS_QUEUED === $download->status) {
            // Reschedule this job at the specified interval.
            self::dispatch($this->fileName, $this->timeStamp)
                ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
        } else if (Download::STATUS_INCOMPLETE === $download->status) {
            // Only queue the next job if the file still exists.
            // A User might delete the file before it finishes.
            if (file_exists($download->file_uri)) {
                // Reschedule this job at the specified interval.
                self::dispatch($this->fileName, $this->timeStamp)
                    ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
            } else {
                // TODO: Add a new Download status: interrupted, unfinished, etc...
                $download->status = Download::STATUS_COMPLETED;
                $download->save();
                $this->releaseLock($download);
            }
        } else { // Download::STATUS_COMPLETED
            if (file_exists($download->file_uri)) {
                $this->handleDownloadDestination($download);

                // Schedule Job that checks to see that the downloaded file was removed from the File system.
                CheckDownloadedFileRemoved::dispatch($download)
                    ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
            } else {
                $this->releaseLock($download);
                // Move the download to the archives table
                ArchiveDownload::dispatch($download);
            }
        }
    }

    /**
     * Returns a single Download model instance.
     *
     * @return Model|null
     */
    protected function getDownload(): Model|null
    {
        $this->downloadQueue->setFilterFileName($this->fileName);
        $this->downloadQueue->setStartDate($this->timeStamp);
        return $this->downloadQueue->first();
    }

    /**
     * Handles transferring a file that has a destination registered.
     *
     * @return void
     */
    protected function handleDownloadDestination(Download $download): void
    {
        // Check if a download destination has been registered for this download.
        $downloadDestination = DownloadDestination::where('download_id', $download->id)
            ->where('status', DownloadDestination::STATUS_WAITING)
            ->first();

        if (null !== $downloadDestination) {
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();
            TrasferDownloadedMedia::dispatch($downloadDestination)->onQueue('transfer');
        }
    }

    /**
     * Removes any lock from a Download.
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
