<?php

namespace App\Media;

final class MediaDynamicRange
{
    const HDR = 'hdr';
    const DOLBY_VISION = 'dovi';

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

    public static function getExpandedDynamicRanges(): array
    {
        return [
            self::DOLBY_VISION => [
                '.DV.',
            ],
        ];
    }
}
