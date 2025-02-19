<?php

namespace App\Media;

/**
 * Represents the different types of media.
 *
 * This class defines constants for various media types and provides utility methods to retrieve the available media types.
 */
final class MediaType
{
    /** @var string The constant for movie media type */
    const MOVIE = 'movie';

    /** @var string The constant for TV episode media type */
    const TV_EPISODE = 'tv episode';

    /** @var string The constant for TV season media type */
    const TV_SEASON = 'tv season';

    /** @var string The constant for book media type */
    const BOOK = 'book';

    /** @var string The constant for music media type */
    const MUSIC = 'music';

    /** @var string The constant for porn media type */
    const PORN = 'porn';

    /** @var string The constant for game media type */
    const GAME = 'game';

    /** @var string The constant for application media type */
    const APPLICATION = 'application';

    /**
     * Retrieves a list of all available media types.
     *
     * This method returns a predefined set of media types as an array. The list includes:
     * - movie
     * - tv episode
     * - tv season
     * - book
     * - music
     * - porn
     * - game
     * - application
     *
     * @return array<string> The list of media types
     */
    public static function getMediaTypes(): array
    {
        // Direct return of the constants, optimized by removing unnecessary method calls
        return [
            self::MOVIE,
            self::TV_EPISODE,
            self::TV_SEASON,
            self::BOOK,
            self::MUSIC,
            self::PORN,
            self::GAME,
            self::APPLICATION,
        ];
    }
}
