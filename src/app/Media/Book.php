<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

final class Book extends Media implements MediaTypeInterface
{
    use ExtensionMetaData, LanguageMetaData;

    // https://www.phpliveregex.com/p/Mxt
    // It's incredibly difficult to find a common pattern among ebook file names.
    // Very little can be done to get more metadata out of the file name.
    const MASK = '/^(.*)\..*$/i';

    // https://www.phpliveregex.com/p/Mxu
    const YEAR_MASK = '/^.*(\d{4}).*$/i';

    //https://www.phpliveregex.com/p/Mxv
    const VOLUME_MASK = '/^.*[book|volume|number][\.\-](\d{1,3})[\.\-].*$/';

    /**
     * Title of the movie.
     *
     * @var string
     */
    private $title;

    /**
     * Year of the release
     *
     * @var string
     */
    private $year;

    /**
     * Book number in a series.
     *
     * @var string
     */
    private $volume;

    /**
     * List of strings that describe various features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * File extension.
     *
     * @var string
     */
    private $extension;

    /**
     * Language.
     *
     * @var string
     */
    private $language;

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (2 > count($this->matches)) return;

        [, $title ] = $this->matches;

        // sanity Check
        $title = (null === $title) ? '' : trim($title);

        $this->title = $this->formatTitle($title);
        $this->year = $this->getYearFromTitle($title);
        $this->volume = $this->getVolumeFromTitle($title);
        $this->tags = $this->formatTags($title);
        $this->extension = $this->getExtension($this->fileName);
        $this->language = $this->getLanguage($this->fileName);
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
            'year'      => $this->year,
            'volume'    => $this->volume,
            'tags'      => $this->tags,
            'extension' => $this->extension,
            'language'  => $this->language,
        ];
    }

    /**
     * Extrapolate the release year from the title.
     *
     * @param string $title
     * @return string
     */
    private function getYearFromTitle(string $title): string
    {
        $matches = [];
        $matchResult = preg_match(self::YEAR_MASK, $title, $matches, PREG_UNMATCHED_AS_NULL);
        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match year from the title.");
        }

        if (0 < count($matches)) {
            return $matches[1];
        } else {
            return '';
        }
    }

    /**
     * Extrapolate the volume from the title.
     *
     * @param string $title
     * @return string|null
     */
    private function getVolumeFromTitle(string $title): string|null
    {
        $matches = [];
        $matchResult = preg_match(self::VOLUME_MASK, $title, $matches, PREG_UNMATCHED_AS_NULL);
        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match volume from the title.");
        }

        if (0 < count($matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }
}
