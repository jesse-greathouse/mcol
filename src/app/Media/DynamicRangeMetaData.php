<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait DynamicRangeMetaData
{
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
     * Throws an exception if matching fails.
     *
     * @param string $fileName
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    public function isHdr(string $fileName): bool
    {
        return $this->isMatching($fileName, $this->getHdrMask(), 'HDR');
    }

    /**
     * Returns boolean if the file name indicates this file is Dolby Vision.
     * Throws an exception if matching fails.
     *
     * @param string $fileName
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    public function isDolbyVision(string $fileName): bool
    {
        return $this->isMatching($fileName, $this->getDolbyVisionMask(), 'Dolby Vision');
    }

    /**
     * Generalized method to check if a file name matches a dynamic range pattern.
     * Throws an exception if matching fails.
     *
     * @param string $fileName
     * @param string $mask The regex mask to use for matching.
     * @param string $type The type of the dynamic range (e.g., 'HDR' or 'Dolby Vision').
     * @return bool
     * @throws MediaMetadataUnableToMatchException
     */
    private function isMatching(string $fileName, string $mask, string $type): bool
    {
        if (!preg_match($mask, $fileName)) {
            throw new MediaMetadataUnableToMatchException("Unable to match $type metadata for: $fileName.");
        }
        return true;
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

        return '/' . $this->regexSafeDots(implode('|', $dynamicRanges)) . '/is';
    }

    /**
     * Replaces dot (.) characters with their regex escaped version.
     *
     * @param string $value The string to escape.
     * @return string The escaped string.
     */
    public function regexSafeDots(string $value): string
    {
        return str_replace('.', '\.', $value);
    }
}
