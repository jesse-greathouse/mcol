<?php

namespace App\Media;

final class MediaResolution
{
    const HD720 = '720p';
    const HD1080 = '1080p';
    const HD4K = '2160p';

    /**
     * Returns a list of a all the media resolutions.
     *
     * @return array
     */
    public static function getMediaResolutions(): array
    {
        return [
            self::HD720,
            self::HD1080,
            self::HD4K,
        ];
    }
}
