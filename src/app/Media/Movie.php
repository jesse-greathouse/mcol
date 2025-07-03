<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a movie, implementing MediaTypeInterface.
 * Provides functionality to extract metadata from a media file name such as title, year, resolution, and other features.
 */
final class Movie extends Media implements MediaTypeInterface
{
    use DynamicRangeMetaData, ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/Mxs
    const STANDARD_MASK = '/^[\d{2}]*(.*)(\d{4}).*(480[p]?|720[p]?|1080[p]?|2160[p]?)(.*)$/is';

    // https://www.phpliveregex.com/p/MDt
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.*)[\-|\.|\s|\(](\d{4})[\-|\.|\s|\)](.*)$/is';

    // https://www.phpliveregex.com/p/N2X
    const ALTERNATIVE_MASK = '/^(.*?)(?:\((\d{4})\)|(\d{4}))(.*)$/is';

    // https://www.phpliveregex.com/p/N3R
    const TITLE_ONLY_MASK = '/^(.*)?(\..*)$/is';

    /**
     * @var string Title of the movie.
     */
    private string $title = '';

    /**
     * @var string Year of the release.
     */
    private ?string $year = null;

    /**
     * @var string|null Video resolution.
     */
    private ?string $resolution = null;

    /**
     * @var array<string> List of strings that describe various features of the media.
     */
    private array $tags = [];

    /**
     * @var string|null extension.
     */
    private ?string $extension = null;

    /**
     * @var string Language.
     */
    private string $language = '';

    /**
     * @var bool Dynamic Range HDR.
     */
    private bool $isHdr = false;

    /**
     * @var bool Dynamic Range Dolby Vision.
     */
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
            $this->tryMatchWithAlternativeMask() => null,
            $this->tryMatchWithTitleOnlyMask() => null,
            default => $this->throwMediaMetadataException(),
        };
    }

    /**
     * Maps the result of regex matching to object properties.
     * Attempts to match the media filename with the resolution mask or fallback to the no-resolution mask.
     */
    public function map(): void
    {
        if ($this->metaData === null) {
            return;
        }

        // Sanitize and assign values to properties.
        $this->title = $this->formatTitle($this->metaData->getTitle() ?? '');
        $this->year = $this->metaData->getYear();
        $this->resolution = $this->metaData->getResolution();
        $this->tags = $this->formatTags(trim($this->metaData->getTags() ?? ''));
        $this->extension = $this->getExtension($this->fileName) ?: null;
        $this->language = $this->getLanguage($this->fileName) ?: '';
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Converts the object properties to an associative array.
     *
     * @return array<string, mixed> Associative array of object properties.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'resolution' => $this->resolution,
            'tags' => $this->tags,
            'extension' => $this->extension,
            'language' => $this->language,
            'is_hdr' => $this->isHdr,
            'is_dolby_vision' => $this->isDolbyVision,
        ];
    }

    /**
     * Tries to match the file name with the `STANDARD_MASK` pattern.
     *
     * The method uses the primary pattern (`STANDARD_MASK`) to extract the title, year, resolution, and tags from the file name.
     *
     * @return bool Returns true if the file name matches the pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithStandardMask(): bool
    {
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match) && count($match) >= 5) {
            // Build a MetaData object using the extracted values from the match.
            $this->metaData = MetaData::build()
                ->withTitle($match[1])          // Extracted title of the episode.
                ->withYear($match[2])           // Year.
                ->withResolution($match[3])    // Video resolution.
                ->withTags($match[4]);         // Tags (e.g., format, quality indicators).

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the `NO_RESOLUTION_MASK` pattern.
     *
     * This method is called as a fallback if `STANDARD_MASK` `ALTERNATIVE_MASK` fails. It matches files without resolution info and sets `resolution` to null.
     *
     * @throws MediaMetadataUnableToMatchException If the file name does not match the `NO_RESOLUTION_MASK` pattern.
     */
    private function tryMatchWithNoResolutionMask(): bool
    {
        if (preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $match) && count($match) >= 4) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])          // Extracted title of the episode.
                ->withYear($match[2])           // Year.
                ->withResolution(null)          // Video resolution.
                ->withTags($match[3]);         // Tags (e.g., format, quality indicators).

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the `ALTERNATIVE_MASK` pattern.
     *
     * If the primary pattern (`STANDARD_MASK`) does not match, this method attempts to match using an alternative pattern (`ALTERNATIVE_MASK`).
     *
     * @return bool Returns true if the file name matches the alternative pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithAlternativeMask(): bool
    {
        if (preg_match(self::ALTERNATIVE_MASK, $this->fileName, $match) && count($match) >= 4) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])          // Extracted title of the episode.
                ->withYear($match[2])           // Year.
                ->withResolution(null)          // Video resolution.
                ->withTags($match[3]);         // Tags (e.g., format, quality indicators).

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the `TITLE_ONLY_MASK` pattern.
     *
     * If the primary pattern (`STANDARD_MASK`) does not match, this method attempts to match using a title only pattern (`TITLE_ONLY_MASK`).
     *
     * @return bool Returns true if the file name matches the alternative pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithTitleOnlyMask(): bool
    {
        if (preg_match(self::TITLE_ONLY_MASK, $this->fileName, $match) && count($match) >= 3) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])  // Extracted title of the episode.
                ->withYear(null)        // Year.
                ->withResolution(null)  // Video resolution.
                ->withTags(null);       // Tags (e.g., format, quality indicators).

            return true;
        }

        return false;
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
            "Unable to match Movie metadata for file: %s \nTried matching with the following patterns: \n%s, \n%s, \n%s \n%s",
            $this->fileName,
            self::STANDARD_MASK,
            self::ALTERNATIVE_MASK,
            self::NO_RESOLUTION_MASK,
            self::TITLE_ONLY_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
