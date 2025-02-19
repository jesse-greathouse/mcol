<?php

namespace App\Media;

/**
 * Class representing a movie, implementing MediaTypeInterface.
 * Provides functionality to extract metadata from a media file name such as title, year, resolution, and other features.
 */
final class Movie extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/Mxs
    const MASK = '/^[\d{2}]*(.*)(\d{4}).*(480[p]?|720[p]?|1080[p]?|2160[p]?)(.*)$/is';

    // https://www.phpliveregex.com/p/MDt
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\-|\.|\s|\(](\d{4})[\-|\.|\s|\)](.*)$/is';

    /**
     * @var string Title of the movie.
     */
    private string $title;

    /**
     * @var string Year of the release.
     */
    private string $year;

    /**
     * @var string|null Video resolution.
     */
    private ?string $resolution;

    /**
     * @var array<string> List of strings that describe various features of the media.
     */
    private array $tags = [];

    /**
     * @var string File extension.
     */
    private string $extension;

    /**
     * @var string Language.
     */
    private string $language;

    /**
     * @var bool Dynamic Range HDR.
     */
    private bool $isHdr;

    /**
     * @var bool Dynamic Range Dolby Vision.
     */
    private bool $isDolbyVision;

    /**
     * Maps the result of regex matching to object properties.
     * Attempts to match the media filename with the resolution mask or fallback to the no-resolution mask.
     *
     * @return void
     */
    public function map(): void
    {
        // Attempt to match with resolution.
        if (preg_match(self::MASK, $this->fileName, $matches) && count($matches) > 4) {
            [, $title, $year, $resolution, $tags] = $matches;
        } else {
            // Try matching without resolution.
            if (!preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $matches) || count($matches) < 4) {
                return;
            }

            [, $title, $year, $tags] = $matches;
            $resolution = null;
        }

        // Sanitize and assign values to properties.
        $this->title = $this->formatTitle($title ?? '');
        $this->year = trim($year ?? '');
        $this->resolution = $resolution ? trim($resolution) : null;
        $this->tags = $this->formatTags(trim($tags ?? ''));
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Returns the mask used for regex matching the media file.
     *
     * @return string The regex mask for the media filename.
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Converts the object properties to an associative array.
     *
     * @return array<string, mixed> Associative array of object properties.
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
