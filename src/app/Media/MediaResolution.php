<?php

namespace App\Media;

/**
 * Class representing media resolutions.
 *
 * This class provides a set of constants for commonly used media resolutions,
 * and a static method to retrieve a list of available resolutions.
 */
final class MediaResolution
{
    /** 720p resolution */
    const HD720 = '720p';

    /** 1080p resolution */
    const HD1080 = '1080p';

    /** 4K resolution */
    const HD4K = '2160p';

    /**
     * Retrieves all media resolutions.
     *
     * Returns an array containing all available media resolutions.
     *
     * @return array<int, string> List of media resolutions.
     */
    public static function getMediaResolutions(): array
    {
        // Directly return the constants in an array.
        return [self::HD720, self::HD1080, self::HD4K];
    }
}
