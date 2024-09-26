<?php

namespace App\Media;

final class MediaDynamicRange
{
    const HDR = 'hdr';
    const DOLBY_VISION = 'dolby vision';

    /**
     * Returns a list of a all the media dynamic ranges.
     *
     * @return array
     */
    public static function getMediaDynamicRanges(): array
    {
        return [
            self::HDR,
            self::DOLBY_VISION,
        ];
    }
}
