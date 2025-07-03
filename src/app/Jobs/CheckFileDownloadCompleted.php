<?php

namespace App\Jobs;

use App\Models\Download;
use App\Models\DownloadDestination;
use App\Models\FileDownloadLock;
use App\Packet\DownloadQueue;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class CheckFileDownloadCompleted
 * Handles checking the status of a file download and processing it accordingly.
 */
class CheckFileDownloadCompleted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Interval in minutes for rescheduling the job
    const SCHEDULE_INTERVAL = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * The name of the file to check.
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

    /**
     * Constructor.
     */
    public function __construct(string $fileName, DateTime $timeStamp)
    {
        $this->fileName = $fileName;
        $this->timeStamp = $timeStamp;
        $this->downloadQueue = new DownloadQueue;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $download = $this->getDownload();

        match (true) {
            $download === null => $this->handleMissingDownload(),
            $download->status === Download::STATUS_QUEUED => $this->handleQueuedDownload(),
            $download->status === Download::STATUS_INCOMPLETE => $this->handleIncompleteDownload($download),
            default => $this->handleCompletedDownload($download),
        };
    }

    /**
     * Handle the case when the download is not found.
     */
    protected function handleMissingDownload(): void
    {
        Log::warning("Expected: {$this->fileName}, in download queue but no record found.");
        $this->releaseLockIfExists();
    }

    /**
     * Handle the case when the download is in the "queued" status.
     */
    protected function handleQueuedDownload(): void
    {
        self::dispatch($this->fileName, $this->timeStamp)
            ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
    }

    /**
     * Handle the case when the download is in the "incomplete" status.
     */
    protected function handleIncompleteDownload(Download $download): void
    {
        if (file_exists($download->file_uri)) {
            self::dispatch($this->fileName, $this->timeStamp)
                ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
        } else {
            $this->completeDownload($download);
        }
    }

    /**
     * Handle the case when the download is in the "completed" status.
     */
    protected function handleCompletedDownload(Download $download): void
    {
        if (file_exists($download->file_uri)) {
            $this->handleDownloadDestination($download);
            CheckDownloadedFileRemoved::dispatch($download)
                ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
        } else {
            $this->releaseLock($download);
            ArchiveDownload::dispatch($download);
        }
    }

    /**
     * Mark the download as completed and release the lock.
     */
    protected function completeDownload(Download $download): void
    {
        $download->status = Download::STATUS_COMPLETED;
        $download->save();
        $this->releaseLock($download);
    }

    /**
     * Returns a single Download model instance based on file name and timestamp.
     */
    protected function getDownload(): ?Download
    {
        $this->downloadQueue->setFilterFileName($this->fileName);
        $this->downloadQueue->setStartDate($this->timeStamp);

        return $this->downloadQueue->first();
    }

    /**
     * Handles transferring a file if a download destination is registered.
     */
    protected function handleDownloadDestination(Download $download): void
    {
        $downloadDestination = DownloadDestination::where('download_id', $download->id)
            ->where('status', DownloadDestination::STATUS_WAITING)
            ->first();

        if ($downloadDestination !== null) {
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();
            TrasferDownloadedMedia::dispatch($downloadDestination)->onQueue('transfer');
        }
    }

    /**
     * Removes the lock from a Download if it exists.
     */
    protected function releaseLock(?Download $download): void
    {
        if ($download === null) {
            return;
        }

        $fileName = basename($download->file_uri);
        $lock = FileDownloadLock::where('file_name', $fileName)->first();

        if ($lock !== null) {
            $lock->delete();
        } else {
            Log::warning("Attempted download lock removal of: $fileName, failed. Lock did not exist.");
        }
    }

    /**
     * Releases the lock if it exists for the current file.
     */
    protected function releaseLockIfExists(): void
    {
        $lock = FileDownloadLock::where('file_name', $this->fileName)->first();
        if ($lock !== null) {
            $lock->delete();
        } else {
            Log::warning("Attempted download lock removal of: {$this->fileName}, failed. Lock did not exist.");
        }
    }
}
