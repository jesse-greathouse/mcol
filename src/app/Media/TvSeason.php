<?php

namespace App\Media;

/**
 * Class representing a TV season, including metadata such as title, season number, resolution, tags, and dynamic range information.
 */
final class TvSeason extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/MxK
    // Regular expression to match the format of a TV season filename
    const MASK = '/^[\d{2}]*(.*)[\.|\-|\s]S(\d{2})[\.|\-|\s](480[p]?|720[p]?|1080[p]?|2160[p]?)?(.+)$/i';

    /** @var string Title of the series */
    private string $title;

    /** @var int Season number */
    private int $season;

    /** @var string Video resolution */
    private string $resolution;

    /** @var array<string> List of strings that describe various features of the media */
    private array $tags = [];

    /** @var string File extension */
    private string $extension;

    /** @var string Language */
    private string $language;

    /** @var bool Dynamic Range HDR */
    private bool $isHdr;

    /** @var bool Dynamic Range Dolby Vision */
    private bool $isDolbyVision;

    /**
     * Maps the result of the regular expression match to the object's properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (count($this->matches) < 5) {
            return;
        }

        // Destructure match groups with default fallbacks
        [, $title, $season, $resolution, $tags] = $this->matches;

        $this->title = $this->formatTitle($title ?? '');
        $this->season = (int) ($season ?? 0); // Explicit cast for clarity
        $this->resolution = $resolution ?? '';
        $this->tags = $this->formatTags($tags ?? '');
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Returns the regular expression mask to match media filenames.
     *
     * @return string The regex mask used to identify TV season filenames.
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Converts the object to an array representation.
     *
     * @return array The array representation of the TV season object.
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
