<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Class representing a game media object with metadata parsing capabilities.
 *
 * This class is responsible for extracting metadata (title, version, release type, etc.)
 * from a string based on predefined patterns and formatting the data accordingly.
 */
final class Game extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData;

    /**
     * Regex pattern to match general media string (e.g., title and version).
     * @see https://www.phpliveregex.com/p/MxA
     */
    private const MASK = '/^[\d{2,3}]*(.*)[\.|\-](.*)\..*$/i';

    /**
     * Regex pattern to match version information in a media string.
     * @see https://www.phpliveregex.com/p/MxD
     */
    private const VERSION_MASK = '/^(.*).*((v|version)\d{1,}[A-Za-z0-9\.\-]*)$/i';

    /**
     * Array of valid release types.
     *
     * @var array<string>
     */
    private const RELEASE_TYPES = [
        'update',
        'updated',
        'hotfix',
        'dlc',
    ];

    /**
     * Title of the game.
     *
     * @var string
     */
    private string $title;

    /**
     * Version of the game.
     *
     * @var string|null
     */
    private ?string $version = null;

    /**
     * Release type (e.g., update, dlc).
     *
     * @var string
     */
    private string $release = '';

    /**
     * Tags describing features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * File extension.
     *
     * @var string
     */
    private string $extension;

    /**
     * Language of the media.
     *
     * @var string
     */
    private string $language;

    /**
     * Maps the result of match to properties.
     *
     * This method processes the media string, extracting title, version, release type,
     * tags, file extension, and language.
     *
     * @return void
     */
    public function map(): void
    {
        if (empty($this->matches)) {
            return;
        }

        $gameStr = trim($this->matches[1] ?? '');
        $cleaned = $gameStr;

        // Extract version from the game string
        $version = $this->getVersionFromGameStr($gameStr);
        if ($version) {
            $cleaned = strstr($gameStr, $version, true) ?: $gameStr;
        }

        // Split the cleaned string based on the separator (period or underscore)
        $separator = strpos($cleaned, '.') === false ? '_' : '.';
        $parts = explode($separator, $cleaned);

        [
            'title'     => $this->title,
            'release'   => $this->release
        ] = $this->extractTitleAndRelease($parts);

        $this->title = $this->formatTitle($this->title);
        $this->version = $version;
        $this->tags = $this->formatTags($gameStr);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
    }

    /**
     * Extracts the title and release type from a list of parts.
     *
     * @param array<string> $parts The split parts of the media string.
     * @return array<string, string> The extracted title and release type.
     */
    private function extractTitleAndRelease(array $parts): array
    {
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

        return [
            'title' => implode(' ', $titleWords),
            'release' => $release
        ];
    }

    /**
     * Returns the mask with which to match the media.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Returns the object as an array.
     *
     * @return array<string, mixed>
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
     * Extracts the version from the game string if available.
     *
     * @param string $gameStr The game string to search in.
     * @return string|null The version string or null if not found.
     * @throws MediaMetadataUnableToMatchException If no valid version is found.
     */
    private function getVersionFromGameStr(string $gameStr): ?string
    {
        preg_match(self::VERSION_MASK, $gameStr, $matches);

        if (count($matches) < 3) {
            throw new MediaMetadataUnableToMatchException("Unable to match media version to the metadata.");
        }

        return $matches[2] ?? null;
    }
}
