<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue,
    Illuminate\Foundation\Bus\Dispatchable,
    Illuminate\Foundation\Queue\Queueable,
    Illuminate\Queue\InteractsWithQueue,
    Illuminate\Queue\SerializesModels,
    Illuminate\Support\Facades\Log;

use App\Media\Application,
    App\Media\Book,
    App\Media\Game,
    App\Media\MediaType,
    App\Media\Movie,
    App\Media\Music,
    App\Media\Porn,
    App\Media\TvEpisode,
    App\Media\TvSeason,
    App\Models\Packet;

use Exception;

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
    private const PACKET_OPTIMIZATION_PROPERTIES = [
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
    private const MEDIA_MAP = [
        MediaType::APPLICATION  => Application::class,
        MediaType::BOOK         => Book::class,
        MediaType::GAME         => Game::class,
        MediaType::MOVIE        => Movie::class,
        MediaType::MUSIC        => Music::class,
        MediaType::PORN         => Porn::class,
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
     * The packet associated with this job.
     *
     * @var Packet|null
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
     * @return void
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
     *
     * @param Packet $packet
     * @return void
     */
    private function processMetaForPacket(Packet $packet): void
    {
        $meta = $this->generateMetaForPacket($packet);

        if (!empty($meta)) {
            $packet->meta = $meta;
            $packet = $this->applyMetadataOptimization($packet, $meta);
            $packet->save();
        }
    }

    /**
     * Generate metadata for a packet based on its media type.
     *
     * @param Packet $packet
     * @return array
     */
    private function generateMetaForPacket(Packet $packet): array
    {
        $meta = [];

        if (isset(self::MEDIA_MAP[$packet->media_type])) {
            $mediaClass = self::MEDIA_MAP[$packet->media_type];

            try {
                $media = new $mediaClass($packet->file_name);
                $meta = $media->toArray();
            } catch (Exception $e) {
                Log::warning($e);
            }
        }

        return $meta;
    }

    /**
     * Apply optimizations to the packet using the provided metadata.
     *
     * @param Packet $packet
     * @param array $meta
     * @return Packet
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
