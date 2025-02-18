<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GeneratePacketMediaType as GeneratePacketMediaTypeJob;

class GeneratePacketMediaType extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'mcol:generate-packet-media-type {--full}';

    /** @var string The console command description. */
    protected $description = 'Populates the media_type field of the packets table for every packet.';

    /**
     * Execute the console command.
     *
     * Dispatches a job to populate the media_type field in the packets table.
     * If the --full option is provided, it triggers a full regeneration.
     */
    public function handle(): void
    {
        $full = $this->option('full') === true;

        GeneratePacketMediaTypeJob::dispatch($full)->onQueue('longruns');
        $this->warn('Queued job for generating packet media types.');
    }
}
