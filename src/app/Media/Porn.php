<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a media file with specific metadata related to adult content.
 * This class processes the media file's name to extract relevant data like title, resolution, tags, and more.
 */
final class Porn extends Media implements MediaTypeInterface
{
    use DynamicRangeMetaData, ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/MDr
    /** @var string The mask to match filenames with resolution */
    const STANDARD_MASK = '/^[\d{2}]*(.*)[\-|\.|\s]XXX[\-|\.|\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\-|\.|\s](.*)\.(.*)$/is';

    // https://www.phpliveregex.com/p/MDq
    /** @var string The mask to match filenames without resolution */
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\-|\.|\s]XXX[\-|\.|\s](.*)$/is';

    /** @var string Title of the movie */
    private string $title = '';

    /** @var string Video Resolution */
    private ?string $resolution = null;

    /** @var array<string> List of strings describing various features of the media */
    private array $tags = [];

    /** @var string|null extension. */
    private ?string $extension = null;

    /** @var string Language */
    private string $language;

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
            $this->tryMatchWithNoResolutionMask() => null,
            default => $this->throwMediaMetadataException(),
        };
    }

    /**
     * Maps the result of regex match to class properties.
     */
    public function map(): void
    {
        if ($this->metaData === null) {
            return;
        }

        // Sanitize and set properties
        $this->title = $this->formatTitle(trim($this->metaData->getTitle() ?? ''));
        $this->resolution = $this->metaData->getResolution();
        $this->tags = $this->formatTags(trim($this->metaData->getTags() ?? ''));
        $this->extension = $this->getExtension($this->fileName) ?: null; // Return null if no extension
        $this->language = $this->getLanguage($this->fileName) ?: ''; // Empty string if no language
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Returns an associative array representing the media properties.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'resolution' => $this->resolution,
            'tags' => $this->tags,
            'extension' => $this->extension,
            'language' => $this->language,
            'is_hdr' => $this->isHdr,
            'is_dolby_vision' => $this->isDolbyVision,
        ];
    }

    /**
     * Attempts to match the file name against the standard mask.
     *
     * If the file name matches the expected pattern, a `MetaData` object is created
     * and populated with the extracted values. The `metaData` property is then assigned this object.
     *
     * @return bool Returns true if a match was found and metadata was successfully set, otherwise false.
     */
    private function tryMatchWithStandardMask(): bool
    {
        // Perform regex match using the standard mask.
        // Ensure the match contains at least 7 elements to avoid incomplete data.
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 5) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])         // Extracted title of the episode.
                ->withResolution($match[2])    // Video resolution.
                ->withTags($match[3]);         // Tags (e.g., format, quality indicators).

            return true; // Matching was successful.
        }

        return false; // No match found.
    }

    /**
     * Attempts to match the file name against the standard mask.
     *
     * If the file name matches the expected pattern, a `MetaData` object is created
     * and populated with the extracted values. The `metaData` property is then assigned this object.
     *
     * @return bool Returns true if a match was found and metadata was successfully set, otherwise false.
     */
    private function tryMatchWithNoResolutionMask(): bool
    {
        if (preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 3) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])          // Extracted title of the episode.
                ->withResolution(null)          // Video resolution.
                ->withTags($match[2]);         // Tags (e.g., format, quality indicators).

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
            "Unable to match Porn metadata for file: %s \nTried matching with the following patterns: \n%s, \n%s",
            $this->fileName,
            self::STANDARD_MASK,
            self::NO_RESOLUTION_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
