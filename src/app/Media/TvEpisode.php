<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

/**
 * Represents a TV episode with metadata like title, season, episode number, resolution, etc.
 * This class processes filenames matching certain patterns and extracts relevant episode information.
 *
 * @package App\Media
 */
final class TvEpisode extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData, DynamicRangeMetaData;

    // https://www.phpliveregex.com/p/N3X
    const STANDARD_MASK = '/^[\d{2}]*(.*)(?:\.|\-|\s|_)(?:S|Se|Sn|Season)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)?(?:E|Ep|Epi|Episode)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)?(.*)?(?:\.|\-|\s|_)?(480[p]?|720[p]?|1080[p]?|2160[p]?)(?:\.|\-|\s|_)?(.*)?$/is';

    // https://www.phpliveregex.com/p/N4s
    const NO_RESOLUTION_MASK = '/^[\d{2}]*(.+)(?:\.|\-|\s|_)(?:S|Se|Sn|Season)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)?(?:E|Ep|Epi|Episode)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)(.+)(?:\..*)?$/is';

    // https://www.phpliveregex.com/p/N3Y
    const BY_DATE_MASK = '/^[\d{2}]*(.*)[\.|\-|\s](\d{2,4})[\.|\-|\s](\d{2})[\.|\-|\s](\d{2})[\.|\-|\s]?(.*)?[\.|\-\s](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-|\s]?(.*)?(?:\..*)$/is';

    // https://www.phpliveregex.com/p/N3Z
    const SIMPLE_EPISODE_MASK = '/^([A-Za-z0-9\s]+)(?:[\s+\-\_\.]+)(\d{1,3})x(\d{1,3})(?:[\s+\-\_\.]+)([^\(\[]+)?((?:[\(\[].*)?(480[p]?|720[p]?|1080[p]?|2160[p]?)(?:.*[\)\]])?)?(.*)?(?:\..*)$/is';

    // https://www.phpliveregex.com/p/N3I
    const UFC_EPISODE_MASK = '/^((.*\bUFC\b.*)\W+(\d{1,})\W+(\w+\W+vs?\W+\w+))((.*)(480[p]?|720[p]?|1080[p]?|2160[p]?))?(.*)(?:\..*)/is';

    // https://www.phpliveregex.com/p/N3n
    const ANIME_EPISODE_MASK = '/^(?:\[[^\]]+\]\s+)?([A-Za-z0-9\s]+)(?:\s+\-\s+)((\d{1,3})(?:\s+\-)?(\s+))?(?:\-\s+)?([^\(\[]+)?((?:[\(\[].*)?(480[p]?|720[p]?|1080[p]?|2160[p]?)(?:.*[\)\]])?)?(.*)?(\..*)$/is';

    // https://www.phpliveregex.com/p/N3o
    const CARTOON_EPISODE_MASK = '/^([\w\s]+)\W+(\d{1,3})x(\d{1,3})\W+([\w\s]+)(?:\W+?)?(.*)?(?:\..*)$/is';

    // https://www.phpliveregex.com/p/N3r
    const ONLY_EPISODE_MASK = '/^([\w\s\.]+)\W+(?:E|Ep|Epi|Episode)(\d{1,3})\W+(480[p]?|720[p]?|1080[p]?|2160[p]?)?([\w\s\.]+)(?:\..*)$/is';

    /** @var string Title of the series. */
    private string $title = '';

    /** @var string Title of the episode. */
    private string $episode_title = '';

    /** @var int Season number. */
    private int $season = 0;

    /** @var int Episode number. */
    private int $episode = 0;

    /** @var string|null Video resolution. */
    private ?string $resolution = null;

    /** @var array<string> List of strings that describe various features of the media. */
    private array $tags = [];

    /** @var string|null File extension. */
    private ?string $extension = null;

    /** @var string Language. */
    private string $language = '';

    /** @var bool Dynamic Range HDR. */
    private bool $isHdr = false;

    /** @var bool Dynamic Range Dolby Vision. */
    private bool $isDolbyVision = false;

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
            $this->tryMatchWithStandardEpisodeMask() => null,
            $this->tryMatchWithNoResolutionMask() => null,
            $this->tryMatchWithDateMask() => null,
            $this->tryMatchWithSimpleEpisodeMask() => null,
            $this->tryMatchWithUfcEpisodeMask() => null,
            $this->tryMatchWithAnimeEpisodeMask() => null,
            $this->tryMatchWithCartoonEpisodeMask() => null,
            $this->tryMatchWithOnlyEpisodeMask() => null,
            default => $this->throwMediaMetadataException(),
        };
    }

    /**
     * Maps the result of match to properties.
     * This method processes the extracted matches and sets object properties accordingly.
     *
     * @return void
     */
    public function map(): void
    {
        if (null === $this->metaData) {
            return;
        }

        // Sanity Check for title and tags
        $this->title = $this->formatTitle(trim($this->metaData->getTitle() ?? ''));
        $this->tags = $this->formatTags(trim($this->metaData->getTags() ?? ''));
        $this->season = (int) ($this->metaData->getSeason() ?? 0); // Default to 0 if no season found
        $this->episode = (int) ($this->metaData->getEpisode() ?? 0); // Default to 0 if no episode found
        $this->episode_title = $this->formatTitle(trim($this->metaData->getEpisodeTitle() ?? ''));
        $this->resolution = $this->metaData->getResolution() ?? null; // Null if no resolution found
        $this->extension = $this->getExtension($this->fileName) ?: null; // Null if no extension found
        $this->language = $this->getLanguage($this->fileName) ?: ''; // Empty string if no language found
        $this->isHdr = $this->isHdr($this->fileName);
        $this->isDolbyVision = $this->isDolbyVision($this->fileName);
    }

    /**
     * Converts the object to an array representation.
     * This method serializes the object's properties into an associative array for easy access.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'episode_title'     => $this->episode_title,
            'season'            => $this->season,
            'episode'           => $this->episode,
            'resolution'        => $this->resolution,
            'tags'              => $this->tags,
            'extension'         => $this->extension,
            'language'          => $this->language,
            'is_hdr'            => $this->isHdr,
            'is_dolby_vision'   => $this->isDolbyVision,
        ];
    }

    /**
     * Tries to match the file name with the UFC_EPISODE_MASK pattern.
     *
     * The method uses the UFC episode pattern to extract UFC-related data (title, episode number, and resolution).
     *
     * @return bool Returns true if the file name matches the UFC episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithUfcEpisodeMask(): bool
    {
        // Perform the regex match with the PREG_UNMATCHED_AS_NULL option
        if (preg_match(self::UFC_EPISODE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 9) {
            $this->metaData = MetaData::build()
                ->withTitle($match[2])
                ->withSeason(1)
                ->withEpisode($match[3])
                ->withEpisodeTitle($match[4])
                ->withResolution($match[7])
                ->withTags($match[8]);
            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the SIMPLE_EPISODE_MASK pattern.
     *
     * The method uses the Anime episode pattern to extract data (title, episode number, episode title).
     *
     * @return bool Returns true if the file name matches the Anime episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithSimpleEpisodeMask(): bool
    {
        // Perform the regex match with the PREG_UNMATCHED_AS_NULL option
        if (preg_match(self::SIMPLE_EPISODE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 9) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason($match[2])
                ->withEpisode($match[3])
                ->withEpisodeTitle($match[4])
                ->withResolution($match[6])
                ->withTags($match[7]);

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the ANIME_EPISODE_MASK pattern.
     *
     * The method uses the Anime episode pattern to extract data (title, episode number, episode title).
     *
     * @return bool Returns true if the file name matches the Anime episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithAnimeEpisodeMask(): bool
    {
        // Perform the regex match with the PREG_UNMATCHED_AS_NULL option
        if (preg_match(self::ANIME_EPISODE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 9) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason(1)
                ->withEpisode($match[3])
                ->withEpisodeTitle($match[5])
                ->withResolution($match[7])
                ->withTags($match[8]);

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the CARTOON_EPISODE_MASK pattern.
     *
     * The method uses the Cartoon episode pattern to extract data (title, season, episode number, episode title).
     *
     * @return bool Returns true if the file name matches the Cartoon episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithCartoonEpisodeMask(): bool
    {
        // Perform the regex match with the PREG_UNMATCHED_AS_NULL option
        if (preg_match(self::CARTOON_EPISODE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 6) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason($match[2])
                ->withEpisode($match[3])
                ->withEpisodeTitle($match[4])
                ->withResolution(null)
                ->withTags($match[5]);

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the ONLY_EPISODE_MASK pattern.
     *
     * The method uses the Only episode pattern to extract data (title, episode number, episode title).
     *
     * @return bool Returns true if the file name matches the Only episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithOnlyEpisodeMask(): bool
    {
        // Perform the regex match with the PREG_UNMATCHED_AS_NULL option
        if (preg_match(self::ONLY_EPISODE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 5) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason(1)
                ->withEpisode($match[2])
                ->withEpisodeTitle($match[4])
                ->withResolution($match[3])
                ->withTags(null);

            return true;
        }

        return false;
    }

    /**
     * Attempts to match the file name against the standard episode mask.
     *
     * If the file name matches the expected episode pattern, a `MetaData` object is created
     * and populated with the extracted values. The `metaData` property is then assigned this object.
     *
     * @return bool Returns true if a match was found and metadata was successfully set, otherwise false.
     */
    private function tryMatchWithStandardEpisodeMask(): bool
    {
        // Perform regex match using the standard episode mask.
        // Ensure the match contains at least 7 elements to avoid incomplete data.
        if (preg_match(self::STANDARD_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 7) {
            // Build a MetaData object using the extracted values from the match.
            $this->metaData = MetaData::build()
                ->withTitle($match[1])         // Extracted title of the episode.
                ->withSeason($match[2])        // Season number.
                ->withEpisode($match[3])       // Episode number.
                ->withEpisodeTitle($match[4])  // Episode title (if available).
                ->withResolution($match[5])    // Video resolution.
                ->withTags($match[6]);         // Tags (e.g., format, quality indicators).

            return true; // Matching was successful.
        }

        return false; // No match found.
    }

    /**
     * Tries to match the file name with the episode pattern (e.g., S01E01) but without a resolution.
     *
     * @return bool Returns true if the file name matches the no resolution pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithNoResolutionMask(): bool
    {
        if (preg_match(self::NO_RESOLUTION_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 5) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason($match[2])
                ->withEpisode($match[3])
                ->withEpisodeTitle($match[4])
                ->withResolution(null)
                ->withTags($match[4]);

            return true;
        }

        return false;
    }

    /**
     * Tries to match the file name with the date-based episode pattern (e.g., MM-DD).
     *
     * This method is used as a final fallback when no other patterns match.
     *
     * @return bool Returns true if the file name matches the date-based episode pattern and data is extracted successfully, false otherwise.
     */
    private function tryMatchWithDateMask(): bool
    {
        if (preg_match(self::BY_DATE_MASK, $this->fileName, $match, PREG_UNMATCHED_AS_NULL) && count($match) >= 8) {
            $this->metaData = MetaData::build()
                ->withTitle($match[1])
                ->withSeason($match[2])
                ->withEpisode($match[3] . '-' .$match[4])
                ->withEpisodeTitle($match[5])
                ->withResolution($match[6])
                ->withTags($match[7]);
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
            "Unable to match TV Episode metadata for file: %s \nTried matching with the following patterns: \n%s, \n%s, \n%s, \n%s, \n%s, \n%s, \n%s, \n%s",
            $this->fileName,
            self::STANDARD_MASK,
            self::NO_RESOLUTION_MASK,
            self::BY_DATE_MASK,
            self::SIMPLE_EPISODE_MASK,
            self::UFC_EPISODE_MASK,
            self::ANIME_EPISODE_MASK,
            self::CARTOON_EPISODE_MASK,
            self::ONLY_EPISODE_MASK
        );

        throw new MediaMetadataUnableToMatchException($message);
    }
}
