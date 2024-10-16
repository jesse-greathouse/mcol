<?php

namespace App\Media;

final class Movie extends Media implements MediaTypeInterface
{
    // https://www.phpliveregex.com/p/Mxs
    const MASK = '/^[\d{2}]*(.*)(\d{4}).*(480[p]?|720[p]?|1080[p]?|2160[p]?)(.*)$/i';

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
     * Video Resolution
     *
     * @var string
     */
    private $resolution;

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
        if (5 > count($this->matches)) return;

        [, $title, $year, $resolution, $tags] = $this->matches;

        $this->title = $this->formatTitle($title);
        $this->year = $year;
        $this->resolution = $resolution;
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
            'title'         => $this->title,
            'year'          => $this->year,
            'resolution'    => $this->resolution,
            'tags'          => $this->tags,
        ];
    }
}
