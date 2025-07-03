<?php

namespace App\Jobs;

use App\Exceptions\MediaMetadataUnableToMatchException;
use App\Media\Application;
use App\Media\Book;
use App\Media\Game;
use App\Media\MediaType;
use App\Media\Movie;
use App\Media\Music;
use App\Media\Porn;
use App\Media\TvEpisode;
use App\Media\TvSeason;
use App\Models\Packet;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to generate and save metadata for packets.
 */
class GeneratePacketMeta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Optimization properties to be copied to the packet.
     *
     * @var string[]
     */
    const PACKET_OPTIMIZATION_PROPERTIES = [
        'resolution',
        'extension',
        'language',
        'is_hdr',
        'is_dolby_vision',
    ];

    /**
     * Map of media types to their corresponding class.
     *
     * @var array
     */
    const MEDIA_MAP = [
        MediaType::APPLICATION => Application::class,
        MediaType::BOOK => Book::class,
        MediaType::GAME => Game::class,
        MediaType::MOVIE => Movie::class,
        MediaType::MUSIC => Music::class,
        MediaType::PORN => Porn::class,
        MediaType::TV_EPISODE => TvEpisode::class,
        MediaType::TV_SEASON => TvSeason::class,
    ];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 86400;

    /**
     * The packet associated with this job.
     *
     * @var Packet|null
     */
    public $packet;

    /**
     * Create a new job instance.
     */
    public function __construct(?Packet $packet = null)
    {
        $this->packet = $packet;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Execute for a single requested packet object.
        if ($this->packet !== null) {
            $this->processMetaForPacket($this->packet);

            return;
        }

        // Execute for all packet objects in the database.
        Packet::lazy()->each(function (Packet $packet) {
            $this->processMetaForPacket($packet);
        });
    }

    /**
     * Process the metadata for a given packet.
     */
    private function processMetaForPacket(Packet $packet): void
    {
        $meta = $this->generateMetaForPacket($packet);

        if (! empty($meta)) {
            $packet->meta = $meta;
            $packet = $this->applyMetadataOptimization($packet, $meta);
            $packet->save();
        }
    }

    /**
     * Generate metadata for a packet based on its media type.
     */
    private function generateMetaForPacket(Packet $packet): array
    {
        $meta = [];

        if (isset(self::MEDIA_MAP[$packet->media_type])) {
            $mediaClass = self::MEDIA_MAP[$packet->media_type];

            try {
                $media = new $mediaClass($packet->file_name);
                $meta = $media->toArray();
            } catch (MediaMetadataUnableToMatchException $e) {
                // Use custom reporting on MediaMetadataUnableToMatchException
                $e->report();
            } catch (Exception $e) {
                Log::warning($e);
            }
        }

        return $meta;
    }

    /**
     * Apply optimizations to the packet using the provided metadata.
     */
    private function applyMetadataOptimization(Packet $packet, array $meta): Packet
    {
        foreach (self::PACKET_OPTIMIZATION_PROPERTIES as $property) {
            if (isset($meta[$property])) {
                $packet->$property = $meta[$property];
            }
        }

        return $packet;
    }
}
