<?php

namespace App\Media;

/**
 * Represents a TV episode with metadata like title, season, episode number, resolution, etc.
 * This class processes filenames matching certain patterns and extracts relevant episode information.
 *
 * @package App\Media
 */
final class TvEpisode extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/MDf
    const MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})E(\d{2})[\.|\-|\s]?(.*)?[\.|\-\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-|\s]?(.*)?$/is';

    // https://www.phpliveregex.com/p/MDl
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})E(\d{2})[\.|\-|\s]?(.*)?[\.|\-|\s]?$/is';

    // https://www.phpliveregex.com/p/MDg
    const BY_DATE_MASK = '/^[\d{2}]*(.*)[\.|\-|\s](\d{2,4})[\.|\-|\s](\d{2})[\.|\-|\s](\d{2})[\.|\-|\s]?(.*)?[\.|\-\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-|\s]?(.*)?$/is';

    /** @var string Title of the series. */
    private string $title;

    /** @var string Title of the episode. */
    private string $episode_title;

    /** @var int Season number. */
    private int $season;

    /** @var int Episode number. */
    private int $episode;

    /** @var string Video resolution. */
    private ?string $resolution;

    /** @var array<string> List of strings that describe various features of the media. */
    private array $tags = [];

    /** @var string File extension. */
    private string $extension;

    /** @var string Language. */
    private string $language;

    /** @var bool Dynamic Range HDR. */
    private bool $isHdr;

    /** @var bool Dynamic Range Dolby Vision. */
    private bool $isDolbyVision;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;

        // Attempt to match the date format first.
        preg_match(self::BY_DATE_MASK, $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);

        if (count($this->matches) === 8) {
            $this->mapDateFormat();
        } else {
            preg_match($this->getMask(), $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);
            $this->map();
        }
    }

    /**
     * Maps the result of match to properties.
     * This method processes the extracted matches and sets object properties accordingly.
     *
     * @return void
     */
    public function map(): void
    {
        // Match including resolution.
        if (count($this->matches) > 6) {
            [, $title, $season, $episode, $episodeTitle, $resolution, $tags] = $this->matches;
        } else {
            // Try matching with no resolution.
            preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);

            if (count($this->matches) < 5) return;

            [, $title, $season, $episode, $tags] = $this->matches;
            $resolution = null;
            $episodeTitle = '';
        }

        // Sanity Check for title and tags
        $this->title = $this->formatTitle($title ?? '');
        $this->tags = $this->formatTags($tags ?? '');
        $this->season = (int) $season;
        $this->episode = (int) $episode;
        $this->episode_title = $this->formatTitle($episodeTitle);
        $this->resolution = $resolution;
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Maps the result of a date-based filename match to properties.
     * This method consolidates the episode number as a combination of month and day.
     *
     * @return void
     */
    public function mapDateFormat(): void
    {
        // Extract month and day as episode number and map.
        [$month, $day] = array_slice($this->matches, 3, 2);
        $episode = $month . $day;

        // Replace month and day with the new episode number
        array_splice($this->matches, 3, 2, [$episode]);
        $this->map();
    }

    /**
     * Returns the mask with which to match the media.
     * This method returns the default regex mask for matching TV episode filenames.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Converts the object to an array representation.
     * This method serializes the object's properties into an associative array for easy access.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'episode_title'     => $this->episode_title,
            'season'            => $this->season,
            'episode'           => $this->episode,
            'resolution'        => $this->resolution,
            'tags'              => $this->tags,
            'extension'         => $this->extension,
            'language'          => $this->language,
            'is_hdr'            => $this->isHdr,
            'is_dolby_vision'   => $this->isDolbyVision,
        ];
    }
}
