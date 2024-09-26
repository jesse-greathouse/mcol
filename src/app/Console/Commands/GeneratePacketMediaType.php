<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GeneratePacketMediaType as GeneratePacketMediaTypeJob;

class GeneratePacketMediaType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-packet-media-type {--full}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates the media_type field of the packets table for every packet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $full = (true === $this->option('full')) ? true : false;

        GeneratePacketMediaTypeJob::dispatch($full)->onQueue('longruns');
        $this->warn("Queued job for Generating Packet Media Types.");
    }
}
