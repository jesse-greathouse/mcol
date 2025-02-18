<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media\MediaType,
    App\Media\Service\Plex;

use \Exception;

/**
 * Command to trigger a Plex media library scan based on a specified media type.
 */
class PlexScan extends Command
{
    /**
     * The type of media to scan.
     *
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcol:plex-scan {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger a scan for Updated media in Plex.';

    /**
     * Executes the console command to scan the Plex media library.
     *
     * @param Plex $plex
     *
     * @throws Exception if Plex service is not configured or media type is invalid.
     */
    public function handle(Plex $plex): void
    {
        if (!$plex->isConfigured()) {
            throw new Exception("The Plex Service is not configured.");
        }

        $plex->scanMediaLibrary($this->getType());
    }

    /**
     * Retrieves and validates the media type.
     *
     * @return string
     *
     * @throws Exception if the media type is invalid.
     */
    public function getType(): string
    {
        if ($this->type === null) {
            $this->type = $this->argument('type');
            $types = MediaType::getMediaTypes();

            if (empty($this->type) || !in_array($this->type, $types, true)) {
                throw new Exception("Invalid Media Type: {$this->type}");
            }
        }

        return $this->type;
    }
}
