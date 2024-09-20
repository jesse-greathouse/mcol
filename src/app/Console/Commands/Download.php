<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

use App\Exceptions\InvalidPacketException,
    App\Jobs\DownloadRequest,
    App\Models\Packet;

class Download extends Command
{
    /**
     * 
     * Packet requested for download.
     *
     * @var Packet
     */
    protected $packet;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:download {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue downloading a packet based on the id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $packet = $this->getPacket();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        DownloadRequest::dispatch($packet);
        $this->warn("Requested packet: {$packet->id} -- {$packet->file_name}");
    }


    public function getPacket(): Packet
    {
        if (null === $this->packet) {
            $id = (int) $this->argument('id');
            $packet = Packet::where('id', $id)->first();
            if (null === $packet) {
               throw new InvalidPacketException("Packet with id: $id was not found.");
            }

            $this->packet = $packet;
        }

        return $this->packet;
    }
}
