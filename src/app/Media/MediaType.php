<?php

namespace App\Media;

final class MediaType
{
    const MOVIE = 'movie';
    const TV_EPISODE = 'tv episode';
    const TV_SEASON = 'tv season';
    const BOOK = 'book';
    const MUSIC = 'music';
    const GAME = 'game';
    const APPLICATION = 'application';

    /**
     * Returns a list of a all the media types.
     *
     * @return array
     */
    public static function getMediaTypes(): array
    {
        return [
            self::MOVIE,
            self::TV_EPISODE,
            self::TV_SEASON,
            self::BOOK,
            self::MUSIC,
            self::GAME,
            self::APPLICATION,
        ];
    }
}
