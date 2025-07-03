<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a music media object.
 *
 * This class handles metadata related to a music album, such as the title, artist,
 * year of release, tags, file extension, and language. It uses regular expressions
 * to match and extract relevant information from media filenames.
 */
final class Music extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/MxT
    // Regex pattern to match general music file naming convention.
    const STANDARD_MASK = '/^[\d{2}]*([A-Za-z0-9_\.]+)+\-([A-Za-z0-9_\.]+|Discography)?\-?(.*)\..*$/i';

    // https://www.phpliveregex.com/p/MxS
    // Regex pattern to match the year in the filename or tags.
    const YEAR_MASK = '/(\d{4})/i';

    /**
     * @var string Title of the music album.
     */
    private string $title = '';

    /**
     * @var string|null Artist of the album.
     */
    private ?string $artist = null;

    /**
     * @var string|null Year of the release, or null if not available.
     */
    private ?string $year = null;

    /**
     * @var string|null Extension.
     */
    private ?string $extension = null;

    /**
     * @var string Language of the media file.
     */
    private string $language = '';

    /**
     * @var array<string> List of tags describing features of the media.
     */
    private array $tags = [];

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
     * Maps the result of match to properties.
     *
     * This method processes the match result to extract and store metadata such as
     * title, artist, year, tags, extension, and language.
     */
    public function map(): void
    {
        if ($this->metaData === null) {
            return;
        }

        $this->title = $this->formatTitle(trim($this->metaData->getTitle() ?? ''));
        $this->artist = $this->formatTitle(trim($this->metaData->getArtist() ?? ''));
        $this->tags = $this->formatTags(trim($this->metaData->getTags() ?? ''));
        $this->year = $this->metaData->getTags() ?? $this->getYearFromTags($this->metaData->getTags());
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
    }

    /**
     * Returns the object as an array.
     *
     * This method returns a structured representation of the media metadata as an associative array.
     *
     * @return array The media metadata as an associative array.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'artist' => $this->artist,
            'year' => $this->year,
            'tags' => $this->tags,
            'extension' => $this->extension,
            'language' => $this->language,
        ];
    }

    /**
     * Extracts the year from the tags string.
     *
     * This method uses a regex pattern to search for a four-digit year within the tag string.
     * If `preg_match` returns `false`, it throws an exception. Otherwise, it returns the year if found,
     * or null if no match is present.
     *
     * @param  string  $tagStr  The string containing tags, including potential year.
     * @return string|null The year if found, or null if not.
     *
     * @throws MediaMetadataUnableToMatchException If `preg_match` returns false.
     */
    private function getYearFromTags(string $tagStr): ?string
    {
        $result = preg_match(self::YEAR_MASK, $tagStr, $matches);
        if ($result === false) {
            throw new MediaMetadataUnableToMatchException("Unable to match Music year with: \"$tagStr\".");
        }

        return $matches[1] ?? null;
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
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 4) {
            $this->metaData = MetaData::build()
                ->withArtist($match[1])         // Extracted title of the Artist.
                ->withTitle($match[2])         // Extracted title of the episode.
                ->withTags($match[3]);         // Tags (e.g., format, quality indicators).

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
            "Unable to match Music metadata for file: %s \nTried matching with the following patterns: \n%s",
            $this->fileName,
            self::STANDARD_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
