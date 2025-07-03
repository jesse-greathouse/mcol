<?php

namespace App\Media;

/**
 * Class MediaDynamicRange
 *
 * This class handles media dynamic ranges, including both HDR and Dolby Vision.
 * It provides methods to retrieve predefined dynamic ranges and their expanded versions.
 */
final class MediaDynamicRange
{
    // Constants representing dynamic range types
    const HDR = 'hdr'; // High Dynamic Range (HDR)

    const DOLBY_VISION = 'dovi'; // Dolby Vision dynamic range

    /**
     * Get a list of all available media dynamic ranges.
     *
     * This method returns a simple array of constant values representing
     * supported dynamic ranges.
     *
     * @return array<string> List of media dynamic ranges (e.g., HDR, Dolby Vision)
     */
    public static function getMediaDynamicRanges(): array
    {
        // Using a constant array for efficiency and ease of maintenance
        return [
            self::HDR,
            self::DOLBY_VISION,
        ];
    }

    /**
     * Get an expanded version of media dynamic ranges.
     *
     * This method returns an associative array mapping dynamic range types to their
     * respective expanded regex patterns or variations.
     * For example, it includes expanded patterns for Dolby Vision.
     *
     * @return array<string, array<string>> Expanded dynamic ranges with regex patterns
     *
     * @see https://regex101.com/ for regex testing and verification
     */
    public static function getExpandedDynamicRanges(): array
    {
        // Define expanded ranges with a clear mapping
        return [
            self::DOLBY_VISION => [
                '.DV.', // Example regex pattern for identifying Dolby Vision content
            ],
        ];
    }
}
