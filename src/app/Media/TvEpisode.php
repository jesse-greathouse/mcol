<?php

namespace App\Media;

final class TvEpisode extends Media implements MediaTypeInterface
{
    // https://www.phpliveregex.com/p/MxL
    const MASK = '/^[\d{2}]*(.*)[\.|\-]S(\d{2})E(\d{2})[\.|\-]?(.*)?[\.|\-](480[p]?|720[p]?|1080[p]?|2160[p]?)[\.|\-]?(.*)?$/i';

    /**
     * Title of the series.
     *
     * @var string
     */
    private $title;

    /**
     * Title of the episode.
     *
     * @var string
     */
    private $episode_title;

    /**
     * Season number
     *
     * @var int
     */
    private $season;

    /**
     * Episode Number
     *
     * @var int
     */
    private $episode;

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
        if (7 > count($this->matches)) return;

        [, $title, $season, $episode, $episodeTitle, $resolution, $tags] = $this->matches;

        $this->title = $this->formatTitle($title);
        $this->season = intval($season);
        $this->episode = intval($episode);
        $this->episode_title = $this->formatTitle($episodeTitle);
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
            'episode_title' => $this->episode_title,
            'season'        => $this->season,
            'episode'       => $this->episode,
            'resolution'    => $this->resolution,
            'tags'          => $this->tags,
        ];
    }
}
