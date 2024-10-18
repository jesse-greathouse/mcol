<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Download,
    App\Models\FileDownloadLock;

class RemoveCompletedDownload implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Two days is the maximum length of time a download can run for now.
     * TODO: Make this more dynamic
     *
     * @var int
     */
    public $timeout = 3;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 1;

    public function __construct(public Download $download){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->releaseLock();
        unlink($this->download->file_uri);

        // Move the download to the archives table
        ArchiveDownload::dispatch($this->download);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return (string) "{$this->download->id}-REMOVE-COMPLETED";
    }

    /**
     * Removes any lock from a Download.
     *
     * @return void
     */
    protected function releaseLock(): void
    {
        $fileName = basename($this->download->file_uri);
        $lock = FileDownloadLock::where('file_name', $fileName)->first();
        if (null !== $lock) {
            $lock->delete();
        }
    }
}
