<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Jobs\CheckFileDownloadCompleted,
    App\Models\FileDownloadLock,
    App\Packet\DownloadQueue;

use \DateTime;

class CheckFileDownloadScheduled implements ShouldQueue
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
        if ($this->isFileQueuedOrDownloading()) {
            //Queue the job that checks if the file is finished downloading.
            CheckFileDownloadCompleted::dispatch($this->fileName, $this->timeStamp)
                ->delay(now()->addMinutes(CheckFileDownloadCompleted::SCHEDULE_INTERVAL));
        } else {
            // Timeout has expired and the Download is not queued.
            // Release the lock on downloading the file.
            $lock = FileDownloadLock::where('file_name', $this->fileName)->first();
            if (null !== $lock) {
                $lock->delete();
            } else {
                Log::warning("Attempted download lock removal of: {$this->fileName}, failed. Lock did not exist.");
            }
        }
    }

    /**
     * Queries the database to see if the file is Queued or Downloading.
     *
     * @return bool
     */
    protected function isFileQueuedOrDownloading(): bool
    {
        $this->downloadQueue->setFilterFileName($this->fileName);
        $this->downloadQueue->setStartDate($this->timeStamp);
        $rs = $this->downloadQueue->get();
        if (0 < count($rs)) {
            return true;
        } else {
            return false;
        }
    }
}
