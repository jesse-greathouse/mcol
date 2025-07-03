<?php

namespace App\Media\Service;

use App\Exceptions\PlexServiceBadConfigurationException;
use App\Exceptions\PlexServiceBadResponseException;
use App\Exceptions\PlexServiceIllegalMediaTypeException;
use App\Media\MediaType;
use App\Settings;
use App\Store\PlexMediaServerSettings;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

final class Plex
{
    // Media type constants
    public const PLEX_MOVIE_MEDIA_TYPE = 'movie';

    public const PLEX_TV_MEDIA_TYPE = 'show';

    public const PLEX_MUSIC_MEDIA_TYPE = 'artist';

    // API endpoints
    public const PLEX_SECTIONS_ENDPOINT = '/library/sections';

    public const PLEX_SCAN_ENDPOINT = '/library/sections/%s/refresh';

    /**
     * Holds the settings for the Plex Media Server service.
     */
    private ?PlexMediaServerSettings $settings;

    /**
     * Maps Mcol Media Types to Plex Media Types.
     */
    private array $mcolToPlexMediaTypeMap = [
        MediaType::MOVIE => self::PLEX_MOVIE_MEDIA_TYPE,
        MediaType::TV_EPISODE => self::PLEX_TV_MEDIA_TYPE,
        MediaType::TV_SEASON => self::PLEX_TV_MEDIA_TYPE,
        MediaType::MUSIC => self::PLEX_MUSIC_MEDIA_TYPE,
    ];

    /**
     * Holds the ID of Plex Media Types.
     */
    private array $plexMediaTypeIndex = [];

    public function __construct(Settings $settings)
    {
        // Set Plex Media Server settings
        $this->settings = $settings->plex_media_server ?? null;
    }

    /**
     * Checks if the Plex service is configured.
     */
    public function isConfigured(): bool
    {
        return $this->settings !== null;
    }

    /**
     * Returns a list of enabled media types.
     */
    public function getEnabledMediaTypes(): array
    {
        return array_keys($this->mcolToPlexMediaTypeMap);
    }

    /**
     * Scans the Plex media library based on the provided media type.
     *
     *
     * @throws PlexServiceBadConfigurationException
     * @throws PlexServiceIllegalMediaTypeException
     */
    public function scanMediaLibrary(string $type): void
    {
        if (! $this->isConfigured()) {
            throw new PlexServiceBadConfigurationException('Plex service is not configured.');
        }

        if (! isset($this->mcolToPlexMediaTypeMap[$type])) {
            throw new PlexServiceIllegalMediaTypeException("Invalid media type: \"$type\".");
        }

        $plexType = $this->mcolToPlexMediaTypeMap[$type];
        $id = $this->getPlexMediaTypeIndex()[$plexType];
        $this->rpcScanMediaLibrary($id);
    }

    /**
     * Retrieves and caches the Plex Media Type index.
     *
     *
     * @throws PlexServiceBadConfigurationException
     */
    public function getPlexMediaTypeIndex(): array
    {
        if (! $this->isConfigured()) {
            throw new PlexServiceBadConfigurationException('Plex service is not configured.');
        }

        // If index is empty, fetch and cache it
        if (empty($this->plexMediaTypeIndex)) {
            $this->plexMediaTypeIndex = collect($this->fetchSections()['Directory'])
                ->pluck('@attributes.key', '@attributes.type')
                ->toArray();
        }

        return $this->plexMediaTypeIndex;
    }

    /**
     * Fetches sections from the Plex API.
     *
     *
     * @throws PlexServiceBadResponseException
     */
    private function fetchSections(): array
    {
        $url = sprintf('%s%s?X-Plex-Token=%s', $this->settings->host, self::PLEX_SECTIONS_ENDPOINT, $this->settings->token);
        $response = Http::get($url);

        if ($response->failed()) {
            throw new PlexServiceBadResponseException(
                sprintf('Plex Response: %s -- %s', $response->status(), $response->reason())
            );
        }

        // Load XML and convert it to an array directly
        $xml = simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOCDATA);

        return $this->xmlToArray($xml);
    }

    /**
     * Converts SimpleXMLElement to an associative array.
     */
    private function xmlToArray(SimpleXMLElement $xml): array
    {
        return json_decode(json_encode($xml), true);
    }

    /**
     * Triggers a media library scan via the Plex API.
     *
     *
     * @throws PlexServiceBadResponseException
     */
    private function rpcScanMediaLibrary(int $id): void
    {
        $endpoint = sprintf(self::PLEX_SCAN_ENDPOINT, $id);
        $url = sprintf('%s%s?X-Plex-Token=%s', $this->settings->host, $endpoint, $this->settings->token);
        $response = Http::get($url);

        if ($response->failed()) {
            throw new PlexServiceBadResponseException(sprintf('Plex Response: %s -- %s', $response->status(), $response->reason()));
        }
    }
}
