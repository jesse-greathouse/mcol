<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait DynamicRangeMetaData
{
    /**
     * Returns boolean if the file name indicates this file is Dolby Vision.
     *
     * @param string $fileName
     * @return bool
     */
    public function isHdr(string $fileName): bool
    {
        $matches = [];

        $matchResult = preg_match($this->getHdrMask(), $fileName, $matches, PREG_UNMATCHED_AS_NULL);

        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match HDR metadata for: $fileName.");
        }

        if (0 < count($matches)) return true;

        return false;
    }

    /**
     * Returns boolean if the file name indicates this file is Dolby Vision.
     *
     * @param string $fileName
     * @return bool
     */
    public function isDolbyVision(string $fileName): bool
    {
        $matches = [];

        $matchResult = preg_match($this->getDolbyVisionMask(), $fileName, $matches, PREG_UNMATCHED_AS_NULL);

        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match Dolby Vision metadata for: $fileName.");
        }

        if (0 < count($matches)) return true;

        return false;
    }

    /**
     * Gets the regex mask for dolby vision.
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
     * Converts the DynamicRanges in App\Media\MediaDynamicRange into a regex mask.
     * https://www.phpliveregex.com/p/MDF
     *
     * @param string $filter
     * @return string
     */
    public function getDynamicRangeMask(string $filter): string
    {
        $list = [];
        $expandedDynamicRange = MediaDynamicRange::getExpandedDynamicRanges();
        if (isset($expandedDynamicRange[$filter])) {
            $list = $expandedDynamicRange[$filter];
        }

        $dynamicRanges = array_merge([$filter], $list);

        return '/' . $this->regexSafeDots(implode('|', $dynamicRanges)) . '/is';
    }

    /**
     * Replace dot (.) characters with regex escaped version.
     *
     * @param string $value
     * @return string
     */
    public function regexSafeDots(string $value): string
    {
        return str_replace('.', '\.', $value);
    }
}
