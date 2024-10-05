<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use App\Jobs\CheckDownloadedFileRemoved,
    App\Models\Download,
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
        } else if ($download->status === Download::STATUS_INCOMPLETE || $download->status === Download::STATUS_QUEUED) {
            // Reschedule this job at the specified interval.
            self::dispatch($this->fileName, $this->timeStamp)
                ->delay(now()->addMinutes(self::SCHEDULE_INTERVAL));
        } else {
            // Schedule Job that checks to see that the downloaded file was removed from the File system.
            CheckDownloadedFileRemoved::dispatch($download)
                    ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
        }
    }

    /**
     * Returns a single Download model instance.
     *
     * @return Model
     */
    public function getDownload(): Model
    {
        $this->downloadQueue->setFilterFileName($this->fileName);
        $this->downloadQueue->setStartDate($this->timeStamp);
        return $this->downloadQueue->first();
    }
}
