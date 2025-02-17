<?php

namespace App\Media;

final class Movie extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/Mxs
    const MASK = '/^[\d{2}]*(.*)(\d{4}).*(480[p]?|720[p]?|1080[p]?|2160[p]?)(.*)$/is';

    // https://www.phpliveregex.com/p/MDt
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\-|\.|\s|\(](\d{4})[\-|\.|\s|\)](.*)$/is';

    /**
     * Title of the movie.
     *
     * @var string
     */
    private $title;

    /**
     * Year of the release
     *
     * @var string
     */
    private $year;

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
        // Match including resolution.
        if (4 < count($this->matches)) {
            [, $title, $year, $resolution, $tags] = $this->matches;
        } else {
            // Try matching with no resolution.
            preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);

            if (4 > count($this->matches)) return;

            [, $title, $year, $tags] = $this->matches;
            $resolution = null;
        }

        // sanity Check
        $title = (null === $title) ? '' : trim($title);
        $year = (null === $year) ? '' : trim($year);
        $tags = (null === $title) ? '' : trim($tags);

        $this->title = $this->formatTitle($title);
        $this->year = $year;
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
            'year'              => $this->year,
            'resolution'        => $this->resolution,
            'tags'              => $this->tags,
            'extension'         => $this->extension,
            'language'          => $this->language,
            'is_hdr'            => $this->isHdr,
            'is_dolby_vision'   => $this->isDolbyVision,
        ];
    }
}
