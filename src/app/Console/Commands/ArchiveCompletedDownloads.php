<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\ArchiveDownload,
    App\Models\Download;

class ArchiveCompletedDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:archive-downloads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Moves completed download records into the doownload_histories table.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;
        foreach(Download::where('status', Download::STATUS_COMPLETED)->get() as $download) {
            ArchiveDownload::dispatch($download)->onQueue('longruns');
            $count++;
        }

        $this->warn("Queued $count completed Downloads for archival.");
    }
}
