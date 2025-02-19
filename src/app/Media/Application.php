<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing an application media object.
 *
 * This class is responsible for extracting, processing, and mapping metadata from application media files.
 * It includes methods for matching and parsing file names to obtain relevant details like title, version,
 * release type, and language.
 */
final class Application extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData;

    /**
     * Regular expression mask for matching the media file's name format.
     *
     * This regex pattern is used to identify and extract the main portion of the application media name.
     * https://www.phpliveregex.com/p/MxF
     *
     * @var string
     */
    private const MASK = '/^[\d{2,3}]*(.*)[\.|\-](.*)\..*$/i';

    /**
     * Regular expression mask for extracting the version of the application.
     *
     * This pattern is used to capture version information from the application media string.
     * https://www.phpliveregex.com/p/MxG
     *
     * @var string
     */
    private const VERSION_MASK = '/^([A-Za-z\.]+)\.((v|version|\d{1,})[0-9\.]*)\..*$/i';

    /**
     * Predefined list of release types for filtering the application's release type.
     *
     * This list includes common release types such as updates, DLCs, hotfixes, etc.
     * These types are extracted from the application media string.
     *
     * @var array<string>
     */
    private const RELEASE_TYPES = [
        'update',
        'updated',
        'hotfix',
        'dlc',
        'keygen',
    ];

    /**
     * Title of the Application.
     *
     * This property holds the title extracted from the media file's name.
     *
     * @var string
     */
    private string $title;

    /**
     * Version of the Application.
     *
     * This property holds the version extracted from the media file's name.
     * If no version is found, this is set to `null`.
     *
     * @var string|null
     */
    private ?string $version = null;

    /**
     * Release type of the Application (e.g., update, hotfix, DLC).
     *
     * This property stores the release type, extracted from the media file name.
     * If no release type is found, it defaults to an empty string.
     *
     * @var string
     */
    private string $release = '';

    /**
     * Tags describing features of the media.
     *
     * This property holds an array of tags extracted from the application string.
     * The tags describe specific features or categories relevant to the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * File extension of the media.
     *
     * This property holds the file extension (e.g., `.exe`, `.apk`) extracted from the file name.
     *
     * @var string
     */
    private string $extension;

    /**
     * Language of the media.
     *
     * This property holds the language extracted from the media file's name or metadata.
     *
     * @var string
     */
    private string $language;

    /**
     * Maps the result of a match to class properties.
     *
     * This method performs the extraction and parsing of the media file's name and populates the class properties.
     * It will:
     * - Extract the application title.
     * - Detect and remove the version string.
     * - Identify the release type (if any).
     * - Set tags, extension, and language for the media.
     *
     * @return void
     */
    public function map(): void
    {
        // Exit early if no matches are found.
        if (empty($this->matches)) {
            return;
        }

        // Extract the application media name and trim any extra spaces.
        [, $applicationStr] = $this->matches;
        $applicationStr = trim($applicationStr ?? '');

        // Extract version and clean the application string.
        $version = $this->getVersionFromApplicationStr($applicationStr);
        $cleaned = $this->removeVersionFromString($applicationStr, $version);

        // Extract the title and release type.
        [$title, $release] = $this->extractTitleAndRelease($cleaned);

        // Set class properties.
        $this->title = $this->formatTitle($title);
        $this->version = $version;
        $this->release = $release;
        $this->tags = $this->formatTags($applicationStr);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
    }

    /**
     * Removes the version string from the application string.
     *
     * @param string $applicationStr The full application string.
     * @param string|null $version The version string to remove.
     *
     * @return string The cleaned application string without the version.
     */
    private function removeVersionFromString(string $applicationStr, ?string $version): string
    {
        if ($version === '') {
            return $applicationStr;
        }

        $position = strpos($applicationStr, $version);
        return $position !== false ? substr($applicationStr, 0, $position) : $applicationStr;
    }

    /**
     * Extracts the title and release type from the cleaned application string.
     *
     * @param string $cleaned The cleaned application string without the version.
     *
     * @return array The extracted title and release type.
     */
    private function extractTitleAndRelease(string $cleaned): array
    {
        // Determine the separator (underscore or period).
        $separator = strpos($cleaned, '.') === false ? '_' : '.';
        $parts = explode($separator, $cleaned);

        // Extract title words and release type.
        $titleWords = [];
        $release = '';
        foreach ($parts as $word) {
            $word = strtolower($word);
            if (in_array($word, self::RELEASE_TYPES, true)) {
                $release = $word;
                break;
            }
            $titleWords[] = $word;
        }

        // Join words to form the title.
        return [implode(' ', $titleWords), $release];
    }

    /**
     * Returns the regular expression mask used to match the media.
     *
     * This method returns the mask pattern that is used to extract media metadata
     * from the media file name (e.g., for title and version matching).
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Returns the object as an associative array.
     *
     * This method converts the object properties into a structured array
     * format that can be used for serialization or other purposes.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'     => $this->title,
            'version'   => $this->version,
            'release'   => $this->release,
            'tags'      => $this->tags,
            'extension' => $this->extension,
            'language'  => $this->language,
        ];
    }

    /**
     * Extracts the version from the application media string.
     *
     * This method uses a regular expression to extract version information from
     * the application media string. If no version can be found, it returns `null`.
     * If the version can't be matched, an exception is thrown.
     *
     * @param string $applicationStr The string representing the application media.
     *
     * @return string|null The extracted version, or `null` if not found.
     * @throws MediaMetadataUnableToMatchException If the version can't be extracted.
     */
    private function getVersionFromApplicationStr(string $applicationStr): ?string
    {
        // Perform regex matching to extract version information.
        preg_match(self::VERSION_MASK, $applicationStr, $matches, PREG_UNMATCHED_AS_NULL);

        // If matching fails or no version is found, throw an exception.
        if (count($matches) < 3) {
            throw new MediaMetadataUnableToMatchException("Unable to match media version to the metadata.");
        }

        // Return the second match as the version, or `null` if not available.
        return $matches[2] ?? null;
    }
}
