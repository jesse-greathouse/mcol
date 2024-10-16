<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

final class Music extends Media implements MediaTypeInterface
{
    // https://www.phpliveregex.com/p/MxT
    const MASK = '/^[\d{2}]*([A-Za-z0-9_\.]+)+\-([A-Za-z0-9_\.]+|Discography)?\-?(.*)\..*$/i';

    // https://www.phpliveregex.com/p/MxS
    const YEAR_MASK = '/(\d{4})/i';

    /**
     * Title of the movie.
     *
     * @var string
     */
    private $title;

    /**
     * Artist of the album.
     *
     * @var string
     */
    private $artist;

    /**
     * Year of the release.
     *
     * @var string
     */
    private $year;

    /**
     * List of strings that describe various features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (4 > count($this->matches)) return;

        [, $artist, $title, $tags] = $this->matches;

        $this->title = $this->formatTitle($title);
        $this->artist = $this->formatTitle($artist);
        $this->year = $this->getYearFromTags($tags);
        $this->tags = $this->formatTags($tags);
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
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'     => $this->title,
            'artist'    => $this->artist,
            'year'      => $this->year,
            'tags'      => $this->tags,
        ];
    }

    /**
     * Formats album title/artist into a readable string.
     *
     * @param string $title
     * @return string
     */
    public function formatTitle(string $title): string
    {
        // Replace dots with spaces.
        $title = str_replace('.', ' ', $title);

        // Replace dots with spaces.
        $title = str_replace('_', ' ', $title);

        // Trim whitespace.
        $title = trim($title);

        return $title;
    }

    /**
     * Extrapolates the year from the tag string.
     *
     * @param string $tagStr
     * @return string|null
     */
    private function getYearFromTags(string $tagStr): string|null
    {
        $matches = [];
        $year = null;

        $result = preg_match(self::YEAR_MASK, $tagStr, $matches);

        if (false === $result) {
            throw new MediaMetadataUnableToMatchException("Unable to match Media year with: \"$tagStr\".");
        }

        if (isset($matches[1])) {
            $year = $matches[1];
        }

        return $year;
    }
}
