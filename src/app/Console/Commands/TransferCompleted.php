<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\CheckDownloadedFileRemoved,
    App\Jobs\TrasferDownloadedMedia,
    App\Models\Download,
    App\Models\DownloadDestination;

/**
 * Class TransferCompleted
 *
 * Handles the transfer of completed downloads by queuing transfer jobs if pending.
 */
class TransferCompleted extends Command
{
    /**
     * URI of file to be transferred.
     *
     * @var string
     */
    protected $uri;

    /**
     * Path where the completed transfer will be stored.
     *
     * @var string
     */
    protected $destination;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:transfer-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queues Transfer jobs on Completed Downloads if pending.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        // Retrieve all completed downloads
        $downloads = Download::where('status', Download::STATUS_COMPLETED)->get();

        // Process each completed download
        foreach ($downloads as $download) {
            $this->handleDownloadDestination($download);
        }
    }

    /**
     * Handles transferring a file that has a registered destination.
     *
     * Checks if a download destination is waiting for transfer and then queues
     * the transfer job and a file removal check job.
     *
     * @param \App\Models\Download $download
     * @return void
     */
    protected function handleDownloadDestination(Download $download): void
    {
        // Check if a download destination is registered for the completed download
        $downloadDestination = DownloadDestination::where('download_id', $download->id)
            ->where('status', DownloadDestination::STATUS_WAITING)
            ->first();

        if ($downloadDestination !== null) {
            // Update the status to queued
            $downloadDestination->status = DownloadDestination::STATUS_QUEUED;
            $downloadDestination->save();

            // Dispatch the transfer job to the 'transfer' queue
            TrasferDownloadedMedia::dispatch($downloadDestination)
                ->onQueue('transfer');

            $this->warn("Queued Download: \"{$download->file_uri}\" for transfer.");

            // Schedule a job to check if the file has been removed from the file system
            CheckDownloadedFileRemoved::dispatch($download)
                ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
        }
    }
}
