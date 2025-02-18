<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GeneratePacketMeta as GeneratePacketMetaJob,
    App\Models\Packet;

/**
 * Class GeneratePacketMeta
 *
 * This command is responsible for populating the 'meta' field of the 'packets' table for every packet.
 */
class GeneratePacketMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-packet-meta {--packet= : The ID of the packet to process (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates the meta field of the packets table for every packet';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \Exception If the packet with the given ID is not found.
     */
    public function handle(): void
    {
        $packet = null;
        $queue = 'longruns';

        // Check if the packet ID option is provided
        if ($this->option('packet')) {
            $id = (int) $this->option('packet'); // Typecast to int for better clarity and efficiency
            $packet = Packet::find($id);

            // If no packet found, throw an exception
            if (!$packet) {
                throw new \Exception("Packet with id: $id could not be found.");
            }

            $queue = 'meta'; // Change queue to 'meta' if specific packet is found
        }

        // Dispatch the job to the appropriate queue
        GeneratePacketMetaJob::dispatch($packet)->onQueue($queue);
        $this->warn('Queued job for Generating Packet Meta.');
    }
}
