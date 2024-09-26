<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Packet,
    App\Packet\MediaType\MediaTypeGuesser;

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
     * Flags the job as a full run (will process every packet).
     *
     * @var boolean
     */
    public bool $isFull;

    /**
     * Create a new job instance.
     */
    public function __construct(bool $full)
    {
        $this->isFull = $full;
    }

    /**
     * Execute the job.
     * 
     */
    public function handle(): void
    {
        if ($this->isFull) {
            $rs = Packet::lazy();
        } else {
            $rs = Packet::whereNull('media_type')->lazy();
        }

        foreach ($rs as $packet) {
            $guesser = new MediaTypeGuesser($packet->file_name);
            $packet->media_type = $guesser->guess();
            $packet->save();
        }
    }
}
