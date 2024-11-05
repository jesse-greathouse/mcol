<?php

namespace App\Media;

final class TvSeason extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/MxK
    const MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})[\.|\-|\s](480[p]?|720[p]?|1080[p]?|2160[p]?)?(.+)$/i';

    /**
     * Title of the series.
     *
     * @var string
     */
    private $title;

    /**
     * Season number
     *
     * @var int
     */
    private $season;

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

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (5 > count($this->matches)) return;

        [, $title, $season, $resolution, $tags] = $this->matches;
        if (null === $title) $title = '';
        if (null === $tags) $tags = '';

        // sanity Check
        $title = (null === $title) ? '' : trim($title);
        $tags = (null === $title) ? '' : trim($tags);

        $this->title = $this->formatTitle($title);
        $this->season = intval($season);
        $this->resolution = $resolution;
        $this->tags = $this->formatTags($tags);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
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
            'season'            => $this->season,
            'resolution'        => $this->resolution,
            'tags'              => $this->tags,
            'extension'         => $this->extension,
            'language'          => $this->language,
            'is_hdr'            => $this->isHdr,
            'is_dolby_vision'   => $this->isDolbyVision,
        ];
    }
}
