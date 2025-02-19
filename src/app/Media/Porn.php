<?php

namespace App\Media;

/**
 * Class representing a media file with specific metadata related to adult content.
 * This class processes the media file's name to extract relevant data like title, resolution, tags, and more.
 *
 * @package App\Media
 */
final class Porn extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/MDr
    /** @var string The mask to match filenames with resolution */
    const MASK = '/^[\d{2}]*(.*)[\-|\.|\s]XXX[\-|\.|\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\-|\.|\s](.*)\.(.*)$/i';

    // https://www.phpliveregex.com/p/MDq
    /** @var string The mask to match filenames without resolution */
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\-|\.|\s]XXX[\-|\.|\s](.*)\.(.*)$/is';

    /** @var string Title of the movie */
    private string $title;

    /** @var string Video Resolution */
    private ?string $resolution = null;

    /** @var array<string> List of strings describing various features of the media */
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
     * Maps the result of regex match to class properties.
     *
     * @return void
     */
    public function map(): void
    {
        // Attempt to match including resolution
        if (preg_match(self::MASK, $this->fileName, $this->matches)) {
            [, $title, $resolution, $tags] = $this->matches;
        } else {
            // Try matching without resolution
            if (!preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $this->matches)) {
                return; // No match found
            }

            [, $title, $tags] = $this->matches;
            $resolution = null;
        }

        // Sanitize and set properties
        $this->title = $this->formatTitle(trim($title ?? ''));
        $this->resolution = $resolution;
        $this->tags = $this->formatTags(trim($tags ?? ''));
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Returns the regex mask for matching media filenames.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Returns an associative array representing the media properties.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'resolution'        => $this->resolution,
            'tags'              => $this->tags,
            'extension'         => $this->extension,
            'language'          => $this->language,
            'is_hdr'            => $this->isHdr,
            'is_dolby_vision'   => $this->isDolbyVision,
        ];
    }
}
