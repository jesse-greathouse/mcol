<?php

namespace App\Media;

final class TvEpisode extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/MDf
    const MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})E(\d{2})[\.|\-|\s]?(.*)?[\.|\-\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-|\s]?(.*)?$/is';

    // https://www.phpliveregex.com/p/MDl
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})E(\d{2})[\.|\-|\s]?(.*)?[\.|\-|\s]?$/is';

    // https://www.phpliveregex.com/p/MDg
    const BY_DATE_MASK = '/^[\d{2}]*(.*)[\.|\-|\s](\d{2,4})[\.|\-|\s](\d{2})[\.|\-|\s](\d{2})[\.|\-|\s]?(.*)?[\.|\-\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-|\s]?(.*)?$/is';

    /**
     * Title of the series.
     *
     * @var string
     */
    private $title;

    /**
     * Title of the episode.
     *
     * @var string
     */
    private $episode_title;

    /**
     * Season number
     *
     * @var int
     */
    private $season;

    /**
     * Episode Number
     *
     * @var int
     */
    private $episode;

    /**
     * Video Resolution
     *
     * @var string
     */
    private $resolution;

    /**
     * List of strings that describe various features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * File extension.
     *
     * @var string
     */
    private $extension;

    /**
     * Language.
     *
     * @var string
     */
    private $language;

    /**
     * Dynamic Range HDR.
     *
     * @var bool
     */
    private $isHdr;

    /**
     * Dynamic Range Dolby Vision.
     *
     * @var bool
     */
    private $isDolbyVision;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;

        // Some TV episodes use a date format like: 2024.11.01 instead of the standard: S01E02
        preg_match(self::BY_DATE_MASK, $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);

        if (8 == count($this->matches)) {
            $this->mapDateFormat();
        } else {
            preg_match($this->getMask(), $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);
            $this->map();
        }
    }

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function map(): void
    {
        // Match including resolution.
        if (6 < count($this->matches)) {
            [, $title, $season, $episode, $episodeTitle, $resolution, $tags] = $this->matches;
        } else {
            // Try matching with no resolution.
            preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);

            if (5 > count($this->matches)) return;

            [, $title, $season, $episode, $tags] = $this->matches;
            $resolution = null;
            $episodeTitle = '';
        }

        // sanity Check
        $title = (null === $title) ? '' : trim($title);
        $tags = (null === $title) ? '' : trim($tags);

        $this->title = $this->formatTitle($title);
        $this->season = intval($season);
        $this->episode = intval($episode);
        $this->episode_title = $this->formatTitle($episodeTitle);
        $this->resolution = $resolution;
        $this->tags = $this->formatTags($tags);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function mapDateFormat(): void
    {
        // consolidate the episode number as month+day and then map.
        [$month, $day] = array_slice($this->matches, 3, 2);
        $episode = $month.$day;

        // Replace the month, day elements with episode.
        array_splice($this->matches, 3, 2, [$episode]);
        $this->map();
    }

    /**
     * Returns the mask with which to match the media.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Returns the object as an array.
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
