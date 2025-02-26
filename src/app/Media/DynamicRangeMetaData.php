<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait DynamicRangeMetaData
{
    const TYPE_HDR = 'HDR';

    const TYPE_DOLBY = 'Dolby Vision';

    /**
     * @var string The regex pattern for HDR matching.
     */
    private string $hdrMask;

    /**
     * @var string The regex pattern for Dolby Vision matching.
     */
    private string $dolbyVisionMask;

    /**
     * Returns boolean if the file name indicates this file is HDR.
     * Returns false if matching fails.
     * Throws an exception if matching fails due to preg_match failure.
     *
     * @param string $fileName
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    public function isHdr(string $fileName): bool
    {
        return $this->isMatching($fileName, $this->getHdrMask(), self::TYPE_HDR);
    }

    /**
     * Returns boolean if the file name indicates this file is Dolby Vision.
     * Returns false if matching fails.
     * Throws an exception if matching fails due to preg_match failure.
     *
     * @param string $fileName
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    public function isDolbyVision(string $fileName): bool
    {
        return $this->isMatching($fileName, $this->getDolbyVisionMask(), self::TYPE_DOLBY);
    }

    /**
     * Generalized method to check if a file name matches a dynamic range pattern.
     * Returns false if matching fails, throws exception on preg_match failure.
     *
     * @param string $fileName
     * @param string $mask The regex mask to use for matching.
     * @param string $type The type of the dynamic range (e.g., 'HDR' or 'Dolby Vision').
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    private function isMatching(string $fileName, string $mask, string $type): bool
    {
        $result = preg_match($mask, $fileName);

        if ($result === false) {
            throw new MediaMetadataUnableToMatchException("Preg_match failed when checking $type metadata from: $fileName.");
        }

        return $result === 1; // If preg_match returns 1, it's a match. If 0, no match.
    }

    /**
     * Gets the regex mask for Dolby Vision.
     *
     * @return string
     */
    public function getDolbyVisionMask(): string
    {
        return $this->getDynamicRangeMask(MediaDynamicRange::DOLBY_VISION);
    }

    /**
     * Gets the regex mask for HDR.
     *
     * @return string
     */
    public function getHdrMask(): string
    {
        return $this->getDynamicRangeMask(MediaDynamicRange::HDR);
    }

    /**
     * Converts the dynamic range filter to a regex mask pattern.
     * @see https://www.phpliveregex.com/p/MDF
     *
     * @param string $filter The filter string (e.g., 'HDR' or 'Dolby Vision').
     * @return string The corresponding regex mask.
     */
    public function getDynamicRangeMask(string $filter): string
    {
        $dynamicRanges = array_merge(
            [$filter],
            MediaDynamicRange::getExpandedDynamicRanges()[$filter] ?? []
        );

        return '/(?<!\w)(' . implode('|', array_map([$this, 'regexSafe'], $dynamicRanges)) . ')(?!\w)/i';
    }

    /**
     * Replaces dot (.) characters with their regex escaped version.
     *
     * @param string $value The string to escape.
     * @return string The escaped string.
     */
    public function regexSafe(string $value): string
    {
        return preg_quote($value, '/');
    }
}
