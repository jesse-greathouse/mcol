<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable,
    Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Jobs\CheckFileDownloadCompleted,
    App\Models\FileDownloadLock,
    App\Packet\DownloadQueue;

use DateTime;

/**
 * Job that checks whether a file download is scheduled.
 */
class CheckFileDownloadScheduled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Constants
    public const SCHEDULE_INTERVAL = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * Name of the file to be processed.
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
     * Create a new job instance.
     *
     * @param string $fileName
     * @param DateTime $timeStamp
     */
    public function __construct(string $fileName, DateTime $timeStamp)
    {
        $this->fileName = $fileName;
        $this->timeStamp = $timeStamp;
        $this->downloadQueue = new DownloadQueue();
    }

    /**
     * Execute the job.
     *
     * This method handles the logic for checking if a file is queued or downloading,
     * and takes appropriate action such as dispatching another job or releasing locks.
     *
     * @return void
     */
    public function handle(): void
    {
        if ($this->isFileQueuedOrDownloading()) {
            // Queue the job that checks if the file is finished downloading.
            CheckFileDownloadCompleted::dispatch($this->fileName, $this->timeStamp)
                ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));

            return;
        }

        // Timeout has expired, and the download is not queued.
        // Release the lock on downloading the file.
        $lock = FileDownloadLock::where('file_name', $this->fileName)->first();

        $lock ? $lock->delete() : Log::warning(
            "Attempted download lock removal of: {$this->fileName}, failed. Lock did not exist."
        );
    }

    /**
     * Queries the database to check if the file is queued or downloading.
     *
     * This method returns true if the file is found in the download queue, otherwise false.
     *
     * @return bool
     */
    protected function isFileQueuedOrDownloading(): bool
    {
        // Set up filtering criteria for the download queue.
        $this->downloadQueue->setFilterFileName($this->fileName);
        $this->downloadQueue->setStartDate($this->timeStamp);

        // Fetch the download queue records and check if any are found.
        $rs = $this->downloadQueue->get();

        return count($rs) > 0;
    }
}
