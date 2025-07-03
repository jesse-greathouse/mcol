<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a TV season, including metadata such as title, season number, resolution, tags, and dynamic range information.
 */
final class TvSeason extends Media implements MediaTypeInterface
{
    use DynamicRangeMetaData, ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/N4a
    // Regular expression to match the format of a TV season filename
    const STANDARD_MASK = '/^[\d{2}]*(.*)\W+(?:S|Se|Sn|Season)\W?(\d{1,3})\W+(480[p]?|720[p]?|1080[p]?|2160[p]?)?(.+)?(\..*)$/is';

    /** @var string Title of the series */
    private string $title = '';

    /** @var int Season number */
    private int $season = 0;

    /** @var string|null Video resolution */
    private ?string $resolution = null;

    /** @var array<string> List of strings that describe various features of the media */
    private array $tags = [];

    /** @var string|null File extension */
    private ?string $extension = null;

    /** @var string Language */
    private string $language = '';

    /** @var bool Dynamic Range HDR */
    private bool $isHdr = false;

    /** @var bool Dynamic Range Dolby Vision */
    private bool $isDolbyVision = false;

    /**
     * Matches the media metadata from the file name.
     *
     * @param  string  $fileName  The name of the file to extract metadata from.
     *
     * @throws MediaMetadataUnableToMatchException If the file name does not match the expected pattern.
     */
    public function match(string $fileName): void
    {
        $this->fileName = $fileName;

        match (true) {
            $this->tryMatchWithStandardMask() => null,
            default => $this->throwMediaMetadataException(),
        };
    }

    /**
     * Maps the result of the regular expression match to the object's properties.
     */
    public function map(): void
    {
        if ($this->metaData === null) {
            return;
        }

        // Assign properties with default fallbacks or nulls
        $this->title = $this->formatTitle(trim($this->metaData->getTitle() ?? '')); // Empty string fallback
        $this->season = (int) ($this->metaData->getSeason() ?? 0); // Default to 0 if no season
        $this->resolution = $this->metaData->getResolution() ?? ''; // Empty string fallback if no resolution
        $this->tags = $this->formatTags(trim($this->metaData->getTags() ?? '')); // Empty string fallback
        $this->extension = $this->getExtension($this->fileName) ?: null; // Null if no extension
        $this->language = $this->getLanguage($this->fileName) ?: ''; // Empty string fallback for language
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Converts the object to an array representation.
     *
     * @return array The array representation of the TV season object.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'season' => $this->season,
            'resolution' => $this->resolution,
            'tags' => $this->tags,
            'extension' => $this->extension,
            'language' => $this->language,
            'is_hdr' => $this->isHdr,
            'is_dolby_vision' => $this->isDolbyVision,
        ];
    }

    /**
     * Attempts to match the file name against the standard season mask.
     *
     * If the file name matches the expected season pattern, a `MetaData` object is created
     * and populated with the extracted values. The `metaData` property is then assigned this object.
     *
     * @return bool Returns true if a match was found and metadata was successfully set, otherwise false.
     */
    private function tryMatchWithStandardMask(): bool
    {
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 6) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])         // Extracted title of the episode.
                ->withSeason($match[2])        // Season number.
                ->withResolution($match[3])    // Video resolution.
                ->withTags($match[4]);         // Tags (e.g., format, quality indicators).

            return true; // Matching was successful.
        }

        return false; // No match found.
    }

    /**
     * Throws an exception indicating that the file name could not be matched with any of the patterns.
     *
     * Constructs an exception message that includes the file name and the patterns attempted.
     *
     * @throws MediaMetadataUnableToMatchException The exception indicating the failure to match the media metadata.
     */
    private function throwMediaMetadataException(): void
    {
        $message = sprintf(
            "Unable to match TV Season metadata for file: %s \nTried matching with the following patterns: \n%s",
            $this->fileName,
            self::STANDARD_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
