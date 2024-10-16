<?php

namespace App\Media\Service;

use Illuminate\Support\Facades\Http;

use App\Exceptions\PlexServiceBadConfigurationException,
    App\Exceptions\PlexServiceBadResponseException,
    App\Exceptions\PlexServiceIllegalMediaTypeException,
    App\Store\PlexMediaServerSettings,
    App\Media\MediaType,
    App\Settings;

final class Plex
{
    const PLEX_MOVIE_MEDIA_TYPE = 'movie';
    const PLEX_TV_MEDIA_TYPE = 'show';
    const PLEX_MUSIC_MEDIA_TYPE = 'artist';

    const PLEX_SECTIONS_ENDPOINT = '/library/sections';
    const PLEX_SCAN_ENDPOINT = '/library/sections/%s/refresh';

    /**
     * Holds the settings for the Plex Media Server service.
     *
     * @var PlexMediaServerSettings
     */
    private $settings;

    /**
     * Maps Mcol Media Types to plex Media Types
     *
     * @var array
     */
    private array $mcolToPlexMediaTypeMap = [
        MediaType::MOVIE        => self::PLEX_MOVIE_MEDIA_TYPE,
        MediaType::TV_EPISODE   => self::PLEX_TV_MEDIA_TYPE,
        MediaType::TV_SEASON    => self::PLEX_TV_MEDIA_TYPE,
        MediaType::MUSIC        => self::PLEX_MUSIC_MEDIA_TYPE,
    ];

    /**
     * Holds the ID of Plex Media Types.
     *
     * @var array
     */
    private array $plexMediaTypeIndex = [];

    public function __construct(Settings $settings)
    {
        if (isset($settings->plex_media_server)) {
            $this->settings = $settings->plex_media_server;
        }
    }

    /**
     * Returns true if there is a configuration for Plex.
     *
     * @return boolean
     */
    public function isConfigured(): bool
    {
        return (null !== $this->settings);
    }

    /**
     * Gets a list of all available MediaType.
     *
     * @return array
     */
    public function getEnabledMediaTypes(): array
    {
        return array_keys($this->mcolToPlexMediaTypeMap);
    }

    /**
     * Scans the plex media library based on the media type.
     *
     * @param string $type
     * @return void
     */
    public function scanMediaLibrary(string $type): void
    {
        if (!$this->isConfigured()) {
            throw new PlexServiceBadConfigurationException("Attempted to use the Plex Service but it is not configured correctly.");
        }

        if (!isset($this->mcolToPlexMediaTypeMap[$type])) {
            throw new PlexServiceIllegalMediaTypeException("Scan Media Library chosen type: \"$type\" is not available.");
        }

        $plexType = $this->mcolToPlexMediaTypeMap[$type];
        $index = $this->getPlexMediaTypeIndex();
        $id = $index[$plexType];
        $this->rpcScanMediaLibrary($id);
    }

    /**
     * Dynamically populates the mediaTypeIndex
     *
     * @return array
     */
    public function getPlexMediaTypeIndex(): array
    {
        if (!$this->isConfigured()) {
            throw new PlexServiceBadConfigurationException("Attempted to use the Plex Service but it is not configured correctly.");
        }

        if (0 >= count($this->plexMediaTypeIndex)) {
            $sections = $this->fetchSections();
            foreach($sections['Directory'] as $directory) {
                ['key' => $key, 'type' => $type] = $directory['@attributes'];
                $this->plexMediaTypeIndex[$type] = $key;
            }
        }

        return $this->plexMediaTypeIndex;
    }

    /**
     * Remote call to the sessions endpoint.
     *
     * @return array
     */
    private function fetchSections(): array
    {
        $url = sprintf('%s%s?X-Plex-Token=%s',
            $this->settings->host,
            self::PLEX_SECTIONS_ENDPOINT,
            $this->settings->token
        );

        $response = Http::get($url);

        if ($response->failed()) {
            throw new PlexServiceBadResponseException(sprintf('Plex Response: %s -- %s',
                $response->status(),
                $response->reason()
            ));
        }

        $xml = simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    /**
     * Remote Call to the Scan endpoint.
     *
     * @param integer $id
     * @return void
     */
    private function rpcScanMediaLibrary(int $id): void
    {
        $endpoint = sprintf(self::PLEX_SCAN_ENDPOINT, $id);
        $url = sprintf('%s%s?X-Plex-Token=%s',
            $this->settings->host,
            $endpoint,
            $this->settings->token
        );

        $response = Http::get($url);

        if ($response->failed()) {
            throw new PlexServiceBadResponseException(sprintf('Plex Response: %s -- %s',
                $response->status(),
                $response->reason()
            ));
        }
    }
}
