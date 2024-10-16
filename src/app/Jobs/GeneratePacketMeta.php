<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Models\Packet,
    App\Packet\Parse;

use \Exception;

class GeneratePacketMeta implements ShouldQueue
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
            $rs = Packet::whereNull('meta')->lazy();
        }

        foreach ($rs as $packet) {
            $meta = [];

            if (isset(Parse::MEDIA_MAP[$packet->media_type])) {
                $mediaClass = Parse::MEDIA_MAP[$packet->media_type];

                try {
                    $media = new $mediaClass($packet->file_name);
                    $meta = $media->toArray();
                } catch(Exception $e) {
                    Log::warning($e);
                }
            }

            $packet->meta = $meta;
            $packet->save();
        }
    }
}
