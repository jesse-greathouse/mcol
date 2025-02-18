<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Foundation\Queue\Queueable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels;

use App\Models\Packet,
    App\Packet\MediaType\MediaTypeGuesser;

/**
 * Class responsible for generating and updating media type for packets.
 */
class GeneratePacketMediaType implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 86400;

    /**
     * Flag to determine if this is a full run (will process every packet).
     *
     * @var bool
     */
    public bool $isFull;

    /**
     * Create a new job instance.
     *
     * @param bool $full Flag to indicate if this is a full run.
     */
    public function __construct(bool $full)
    {
        $this->isFull = $full;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Use lazy() for memory efficiency to load results in chunks
        $query = $this->isFull
            ? Packet::lazy() // If it's a full run, process all packets
            : Packet::whereNull('media_type')->lazy(); // Process only packets without a media_type

        // Loop through the packets and update their media type
        foreach ($query as $packet) {
            $guesser = new MediaTypeGuesser($packet->file_name);
            $packet->media_type = $guesser->guess();
            $packet->save(); // Save the updated media type to the database
        }
    }
}
