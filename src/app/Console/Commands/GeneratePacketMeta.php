<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GeneratePacketMeta as GeneratePacketMetaJob;

class GeneratePacketMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-packet-meta {--full}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates the meta field of the packets table for every packet';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $full = (true === $this->option('full')) ? true : false;

        GeneratePacketMetaJob::dispatch($full)->onQueue('longruns');
        $this->warn("Queued job for Generating Packet Meta.");
    }
}
