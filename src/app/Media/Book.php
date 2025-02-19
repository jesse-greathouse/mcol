<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a book within a media library.
 *
 * This class handles the extraction and management of metadata related to books, including
 * the title, release year, volume (if applicable), tags, file extension, and language.
 * It uses regular expressions to match specific patterns in the book's title to extract
 * relevant information such as the year of release and volume in a series.
 *
 * The class provides methods for mapping the extracted metadata to properties,
 * converting the object to an associative array, and retrieving the media's
 * matching regex pattern.
 *
 * Regular expressions used:
 * - MASK: Pattern for matching the file name and extracting the base title.
 * - YEAR_MASK: Pattern for extracting the release year from the title.
 * - VOLUME_MASK: Pattern for identifying the volume number in the title.
 *
 * @package App\Media
 */
final class Book extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/Mxt
    // It's incredibly difficult to find a common pattern among ebook file names.
    // Very little can be done to get more metadata out of the file name.
    const MASK = '/^(.*)\..*$/i';

    // https://www.phpliveregex.com/p/Mxu
    // Pattern for matching a 4-digit year in the title
    const YEAR_MASK = '/^.*(\d{4}).*$/i';

    // https://www.phpliveregex.com/p/Mxv
    // Pattern to match book/volume/number and capture the volume number.
    const VOLUME_MASK = '/^.*[book|volume|number][\.\-](\d{1,3})[\.\-].*$/i';

    /**
     * Title of the book.
     *
     * @var string
     */
    private string $title = '';

    /**
     * Year of the book's release.
     *
     * @var string
     */
    private string $year = '';

    /**
     * Book number in a series.
     *
     * @var string|null
     */
    private ?string $volume = null;

    /**
     * List of tags describing various features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * File extension of the book.
     *
     * @var string
     */
    private string $extension = '';

    /**
     * Language of the book.
     *
     * @var string
     */
    private string $language = '';

    /**
     * Maps the result of a regex match to the object's properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (count($this->matches) < 2) {
            return;
        }

        [, $title] = $this->matches;

        // Sanitize title to prevent issues if null or empty
        $title = trim($title ?? '');

        // Map metadata from the title
        $this->title = $this->formatTitle($title);
        $this->year = $this->getYearFromTitle($title);
        $this->volume = $this->getVolumeFromTitle($title);
        $this->tags = $this->formatTags($title);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
    }

    /**
     * Returns the regex pattern to match the media file name.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Converts the object to an associative array for easier handling.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'     => $this->title,
            'year'      => $this->year,
            'volume'    => $this->volume,
            'tags'      => $this->tags,
            'extension' => $this->extension,
            'language'  => $this->language,
        ];
    }

    /**
     * Extracts the year from the book's title using a regex pattern.
     *
     * @param string $title The title of the book.
     * @return string The extracted year or an empty string if no match.
     * @throws MediaMetadataUnableToMatchException If no year is found.
     */
    private function getYearFromTitle(string $title): string
    {
        // Try to match the year in the title using regex
        if (preg_match(self::YEAR_MASK, $title, $matches) === 1) {
            return $matches[1] ?? '';
        }

        // Throw exception if no match is found
        throw new MediaMetadataUnableToMatchException("Unable to match year from the title.");
    }

    /**
     * Extracts the volume from the book's title using a regex pattern.
     *
     * @param string $title The title of the book.
     * @return string|null The extracted volume or null if no match.
     * @throws MediaMetadataUnableToMatchException If no volume is found.
     */
    private function getVolumeFromTitle(string $title): ?string
    {
        // Try to match the volume in the title using regex
        if (preg_match(self::VOLUME_MASK, $title, $matches) === 1) {
            return $matches[1] ?? null;
        }

        // Throw exception if no match is found
        throw new MediaMetadataUnableToMatchException("Unable to match volume from the title.");
    }
}
