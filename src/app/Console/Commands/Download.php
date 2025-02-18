<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Exceptions\InvalidPacketException,
    App\Jobs\DownloadRequest,
    App\Models\Packet;

use \Exception;

class Download extends Command
{
    /** @var Packet|null Packet requested for download. */
    protected ?Packet $packet = null;

    /** @var string The name and signature of the console command. */
    protected string $signature = 'mcol:download {id}';

    /** @var string The console command description. */
    protected string $description = 'Queue downloading a packet based on the ID';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $packet = $this->fetchPacket();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        DownloadRequest::dispatch($packet);
        $this->warn("Requested packet: {$packet->id} -- {$packet->file_name}");

        return Command::SUCCESS;
    }

    /**
     * Retrieves the packet by ID, caching the result.
     *
     * @throws InvalidPacketException If the packet is not found.
     */
    private function fetchPacket(): Packet
    {
        if ($this->packet !== null) {
            return $this->packet;
        }

        $id = (int) $this->argument('id');
        $packet = Packet::find($id);

        if ($packet === null) {
            throw new InvalidPacketException("Packet with ID: $id was not found.");
        }

        return $this->packet = $packet;
    }
}
