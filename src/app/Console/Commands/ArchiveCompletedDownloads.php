<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\ArchiveDownload,
    App\Models\Download;

class ArchiveCompletedDownloads extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'mcol:archive-downloads';

    /** @var string The console command description. */
    protected $description = 'Moves completed download records into the download_histories table.';

    /**
     * Execute the console command.
     *
     * Efficiently dispatches jobs to archive completed downloads.
     */
    public function handle(): void
    {
        $downloads = Download::where('status', Download::STATUS_COMPLETED)->get();

        foreach ($downloads as $download) {
            ArchiveDownload::dispatch($download)->onQueue('longruns');
        }

        $this->warn("Queued {$downloads->count()} completed downloads for archival.");
    }
}
