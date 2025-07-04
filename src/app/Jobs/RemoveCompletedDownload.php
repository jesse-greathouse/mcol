<?php

namespace App\Jobs;

use App\Models\Download;
use App\Models\FileDownloadLock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveCompletedDownload implements ShouldBeUnique, ShouldQueue
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

    /**
     * The download object associated with this job.
     *
     * @var \App\Models\Download
     */
    public function __construct(public Download $download) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Release the lock before processing the file
        $this->releaseLock();

        // Only attempt to delete the file if it exists to prevent errors
        if (file_exists($this->download->file_uri)) {
            unlink($this->download->file_uri);
        }

        // Dispatch the job to move the download to the archives table
        ArchiveDownload::dispatch($this->download);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        // Return a string as the unique identifier for this job
        return (string) "{$this->download->id}-REMOVE-COMPLETED";
    }

    /**
     * Removes any lock from a Download by deleting the corresponding lock entry.
     */
    protected function releaseLock(): void
    {
        // Extract the file name from the file URI
        $fileName = basename($this->download->file_uri);

        // Delete the lock if it exists
        FileDownloadLock::where('file_name', $fileName)->first()?->delete();
    }
}
