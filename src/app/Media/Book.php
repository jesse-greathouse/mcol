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
    const STANDARD_MASK = '/^(.*)\..*$/i';

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
     * @var string|null
     */
    private ?string $year = null;

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
     * @var string|null extension.
     */
    private ?string $extension = null;

    /**
     * Language of the book.
     *
     * @var string
     */
    private string $language = '';

    /**
     * Matches the media metadata from the file name.
     *
     * @param string $fileName The name of the file to extract metadata from.
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
     * Maps the result of a regex match to the object's properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (null === $this->metaData) {
            return;
        }

        // Sanitize title to prevent issues if null or empty
        $title = trim($this->metaData->getTitle() ?? '');

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
        return self::STANDARD_MASK;
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
     * @return string|null The extracted year or an empty string if no match.
     * @throws MediaMetadataUnableToMatchException If a regex error occurs.
     */
    private function getYearFromTitle(string $title): string|null
    {
        $matches = [];

        $result = preg_match(self::YEAR_MASK, $title, $matches);

        if ($result === false) {
            throw new MediaMetadataUnableToMatchException("Regex error while extracting year from Book title: $title.");
        }

        return $result === 0 ? null : ($matches[1] ?? '');
    }

    /**
     * Extracts the volume from the book's title using a regex pattern.
     *
     * @param string $title The title of the book.
     * @return string|null The extracted volume or null if no match.
     * @throws MediaMetadataUnableToMatchException If a regex error occurs.
     */
    private function getVolumeFromTitle(string $title): ?string
    {
        $matches = [];

        $result = preg_match(self::VOLUME_MASK, $title, $matches);

        if ($result === false) {
            throw new MediaMetadataUnableToMatchException("Regex error while extracting volume from Book title: $title.");
        }

        return $result === 0 ? null : ($matches[1] ?? null);
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
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 2) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1]); // Extracted Title String.

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
            "Unable to match Book metadata for file: %s \nTried matching with the following patterns: \n%s",
            $this->fileName,
            self::STANDARD_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
