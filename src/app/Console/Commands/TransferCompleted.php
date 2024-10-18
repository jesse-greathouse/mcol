<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\CheckDownloadedFileRemoved,
    App\Jobs\TrasferDownloadedMedia,
    App\Models\Download,
    App\Models\DownloadDestination;

class TransferCompleted extends Command
{
    /**
     *
     * Uri of file to be transferred.
     *
     * @var String
     */
    protected $uri;

    /**
     *
     * Path of where the completed transfer will be.
     *
     * @var String
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
     */
    public function handle()
    {
        $downloads = Download::where('status', Download::STATUS_COMPLETED)->get();
        foreach($downloads as $download) {
            $this->handleDownloadDestination($download);
        }
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

            $this->warn("Queued Download: \"{$download->file_uri}\" for transfer.");
            // Schedule Job that checks to see that the downloaded file was removed from the File system.
            CheckDownloadedFileRemoved::dispatch($download)
                ->delay(now()->addMinutes(CheckDownloadedFileRemoved::SCHEDULE_INTERVAL));
        }
    }
}
