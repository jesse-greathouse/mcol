<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Media\MediaType,
    App\Media\Service\Plex;

use \Exception;

class PlexScan extends Command
{
    /**
     *
     * Media Type to scan.
     *
     * @var String
     */
    protected $type;

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
    protected $description = 'Transfer a file';

    /**
     * Execute the console command.
     */
    public function handle(Plex $plex)
    {
        if (!$plex->isConfigured()) {
            throw new Exception("The Plex Service is not configured.");
        }

        $plex->scanMediaLibrary($this->getType());
    }


    /**
     * Gets and validates the uri as input.
     *
     * @return string
     */
    public function getType(): string
    {
        if (null === $this->type) {
            $this->type = $this->argument('type');
            $types = MediaType::getMediaTypes();
            if (null === $this->type || '' === $this->type || !in_array($this->type, $types)) {
                throw new Exception("Invalid Media Type: {$this->type}");
            }
        }

        return $this->type;
    }
}
