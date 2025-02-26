<?php

namespace App\Media;

use Stringy\Stringy as S;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Abstract class representing media metadata mapping and formatting.
 *
 * This class provides functionality to map and format metadata for media files based on the provided file name.
 * It uses a regular expression mask to extract relevant information from the file name and tags.
 *
 * @package App\Media
 */
abstract class Media
{
    /**
     * Regular expression pattern to match common media file tags.
     *
     * This pattern is used to identify various tags in media file names and metadata strings.
     *
     * @var string
     */
    const TAG_MASK = '/(UHD|BluRay|BDRip|DVDRip|WEB|WEBRiP|Anime|WebDL|WEB\-DL|h\.264|x\.264|h264|x264|h\.265|h265|x265|XviD|MP3|CD|FLAC|ROM|SNES|NSW|MacOS|AMZN|NF|ROKU|TVING|HIST|PCOCK|HMAX|DSNP|ATVP|AV1|10\-bit|DD2\.0|DDP2\.0|DDP5\.1|DD5\.1|DDP7\.1|DD7\.1|DD|Opus|HEVC|HDTV|AAC|AAC2\.0|Atmos|DUBBED|DUAL|Remux|TrueHD|HDR|DV|DoVi|DTSHD|HYBRID|24BIT|16BIT|44kHz|x64)/i';

    /**
     * Holds the result of the regex match operation.
     *
     * @var array
     */
    protected array $matches = [];

    /**
     * The metadata object containing additional media details.
     *
     * This property holds an instance of the MetaData class, providing structured information
     * about the media file. It is nullable and defaults to null.
     *
     * @var ?MetaData
     */
    protected ?MetaData $metaData = null;

    /**
     * The file name from which the metadata was derived.
     *
     * @var string
     */
    protected string $fileName;

    /**
     * Constructor to initialize the media object.
     *
     * @param ?string $fileName The name of the file to extract metadata from.
     * @throws MediaMetadataUnableToMatchException If the file name does not match the expected pattern.
     */
    public function __construct(?string $fileName = null)
    {
        $this->fileName = $fileName;

        if (null !== $fileName) {
            // Extract the metadata from the file name.
            $this->match($fileName);

            // Map the match result to properties
            $this->map();
        }
    }

    /**
     * Abstract method to map the result of regex match to properties.
     *
     * Implementations should populate the properties of the class with the matched values.
     *
     * @return void
     */
    public abstract function match(string $fileName): void;

    /**
     * Abstract method to matches the media metadata from the file name.
     *
     * @param string $fileName the name of the file with which to perform the data extraction.
     * @return void.
     */
    public abstract function map(): void;

    /**
     * Formats a title into a readable string.
     *
     * The formatting involves replacing dots with spaces, trimming whitespace, and converting the string to title case.
     *
     * @param string $title The raw title to format.
     * @return string The formatted title.
     */
    public function formatTitle(string $title): string
    {
        // Return early if the title is empty
        if ($title === '') {
            return $title;
        }

        // Replace dots with spaces and trim whitespace
        $title = trim(str_replace(['.', '_'], ' ', $title));

        // Convert to title case
        return S::create($title)->toTitleCase();
    }

/**
     * Formats a string of tags into a list of normalized tags.
     *
     * The tags are extracted using a regular expression, and all extracted tags are converted to lowercase.
     *
     * @param string $tagStr The string containing the tags to format.
     * @return array The list of formatted tags.
     */
    public function formatTags(string $tagStr): array
    {
        // Return an empty array if the input string is empty
        if (empty($tagStr)) {
            return [];
        }

        // Perform regex match to extract tags
        preg_match_all(self::TAG_MASK, $tagStr, $matches);

        // If no tags are found, return an empty array
        if (empty($matches[1])) {
            return [];
        }

        // Extract tags from the first capture group, convert to lowercase
        return array_map('strtolower', $matches[1]);
    }
}
