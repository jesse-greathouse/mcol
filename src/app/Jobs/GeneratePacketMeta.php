<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

use App\Media\Application,
    App\Media\Book,
    App\Media\Game,
    App\Media\MediaType,
    App\Media\Movie,
    App\Media\Music,
    App\Media\TvEpisode,
    App\Media\TvSeason,
    App\Models\Packet;

use \Exception;

class GeneratePacketMeta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MEDIA_MAP = [
        MediaType::APPLICATION  => Application::class,
        MediaType::BOOK         => Book::class,
        MediaType::GAME         => Game::class,
        MediaType::MOVIE        => Movie::class,
        MediaType::MUSIC        => Music::class,
        MediaType::TV_EPISODE   => TvEpisode::class,
        MediaType::TV_SEASON    => TvSeason::class,
    ];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 86400;

    /**
     * Packet for the context of this job.
     *
     * @var Packet
     */
    public $packet;

    /**
     * Create a new job instance.
     *
     * @param Packet|null $packet
     */
    public function __construct(Packet $packet = null)
    {
        $this->packet = $packet;
    }

    /**
     * Execute the job.
     *
     */
    public function handle(): void
    {
        if (null !== $this->packet) {
            $this->makeMeta($this->packet);
        } else {
            $rs = Packet::whereNull('meta')->lazy();
            foreach ($rs as $packet) {
                $this->makeMeta($packet);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param Packet $packet
     * @return void
     */
    private function makeMeta(Packet $packet): void
    {
        $meta = [];

        if (isset(self::MEDIA_MAP[$packet->media_type])) {
            $mediaClass = self::MEDIA_MAP[$packet->media_type];

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
