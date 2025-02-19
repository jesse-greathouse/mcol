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
    const MASK = '/^[\d{2}]*([A-Za-z0-9_\.]+)+\-([A-Za-z0-9_\.]+|Discography)?\-?(.*)\..*$/i';

    // https://www.phpliveregex.com/p/MxS
    // Regex pattern to match the year in the filename or tags.
    const YEAR_MASK = '/(\d{4})/i';

    /**
     * @var string Title of the music album.
     */
    private string $title;

    /**
     * @var string Artist of the album.
     */
    private string $artist;

    /**
     * @var string|null Year of the release, or null if not available.
     */
    private ?string $year = null;

    /**
     * @var string File extension of the media.
     */
    private string $extension;

    /**
     * @var string Language of the media file.
     */
    private string $language;

    /**
     * @var array<string> List of tags describing features of the media.
     */
    private array $tags = [];

    /**
     * Maps the result of match to properties.
     *
     * This method processes the match result to extract and store metadata such as
     * title, artist, year, tags, extension, and language.
     *
     * @return void
     */
    public function map(): void
    {
        if (count($this->matches) < 4) {
            return; // Early exit for fewer matches
        }

        [, $artist, $title, $tags] = $this->matches;

        $this->title = $this->formatTitle($title ?? '');
        $this->artist = $this->formatTitle($artist ?? '');
        $this->year = $this->getYearFromTags($tags ?? '');
        $this->tags = $this->formatTags($tags ?? '');
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
    }

    /**
     * Returns the mask with which to match the media.
     *
     * This is the regular expression pattern used to match and extract media metadata.
     *
     * @return string The regex pattern used for matching.
     */
    public function getMask(): string
    {
        return self::MASK;
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
            'title'     => $this->title,
            'artist'    => $this->artist,
            'year'      => $this->year,
            'tags'      => $this->tags,
            'extension' => $this->extension,
            'language'  => $this->language,
        ];
    }

    /**
     * Formats the album title or artist into a readable string.
     *
     * This method replaces underscores and periods with spaces, then trims the result.
     *
     * @param string $title The title or artist name to format.
     *
     * @return string The formatted title or artist name.
     */
    public function formatTitle(string $title): string
    {
        return trim(str_replace(['.', '_'], ' ', $title));
    }

    /**
     * Extracts the year from the tags string.
     *
     * This method uses a regex pattern to search for a four-digit year within the tag string.
     * If the year is found, it returns it. Otherwise, it throws an exception.
     *
     * @param string $tagStr The string containing tags, including potential year.
     *
     * @return string|null The year if found, or null if not.
     *
     * @throws MediaMetadataUnableToMatchException If no valid year is found.
     */
    private function getYearFromTags(string $tagStr): ?string
    {
        if (preg_match(self::YEAR_MASK, $tagStr, $matches) === false) {
            throw new MediaMetadataUnableToMatchException("Unable to match Media year with: \"$tagStr\".");
        }

        return $matches[1] ?? null;
    }
}
