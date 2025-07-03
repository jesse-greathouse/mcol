<?php

namespace App\Console\Commands;

use App\Jobs\GenerateFileFirstAppearance as GenerateFileFirstAppearanceJob;
use Illuminate\Console\Command;

/**
 * Command to populate the first appearances table from the Packets table.
 */
class GenerateFirstAppearance extends Command
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'mcol:generate-first-appearance';

    /** @var string The console command description. */
    protected $description = 'Populates the first appearances table with information from the Packets table.';

    /**
     * Execute the console command.
     *
     * Dispatches a job to generate first appearances and queues it under 'longruns'.
     */
    public function handle(): void
    {
        GenerateFileFirstAppearanceJob::dispatch()->onQueue('longruns');
        $this->warn('Queued job for Generating First Appearances.');
    }
}
