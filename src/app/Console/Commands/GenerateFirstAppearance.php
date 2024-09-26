<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GenerateFileFirstAppearance as GenerateFileFirstAppearanceJob;

class GenerateFirstAppearance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-first-appearance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates the first appearances table with information from the Packets table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        GenerateFileFirstAppearanceJob::dispatch()->onQueue('longruns');
        $this->warn("Queued job for Generating First Appearances.");
    }
}
