<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GeneratePacketMeta as GeneratePacketMetaJob,
    App\Models\Packet;

use Exception;

class GeneratePacketMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:generate-packet-meta {--packet=}';

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
        $packet = null;
        $queue = 'longruns';

        if ($this->option('packet')) {
            $id = intval($this->option('packet'));
            $packet = Packet::find($id);
            if (null === $packet) {
                throw new Exception("Packet with id: $id could not be found.");
            }

            $queue = 'meta';
        }

        GeneratePacketMetaJob::dispatch($packet)->onQueue($queue);
        $this->warn("Queued job for Generating Packet Meta.");
    }
}
