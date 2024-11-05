<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait LanguageMetaData
{

    /**
     * Returns a language value from a fileName.
     *
     * @param string $fileName
     * @return string
     */
    public function getLanguage(string $fileName): string
    {
        $matches = [];

        $matchResult = preg_match($this->getLanguageMask(), $fileName, $matches, PREG_UNMATCHED_AS_NULL);

        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match language metadata for: $fileName.");
        }

        if (0 >= count($matches)) return '';

        [$language] = $matches;

        return strtolower($language);
    }

    /**
     * Converts the Languages in App\Media\MediaLanguage into a regex mask.
     * https://www.phpliveregex.com/p/MDD
     *
     * @return string
     */
    public function getLanguageMask(): string
    {
        $languages = MediaLanguage::getMediaLanguages();
        foreach(MediaLanguage::getExpandedLanguages() as $language => $list) {
            $languages = array_merge($languages, $list);
        }

        return '/' . implode('|', $languages) . '/is';
    }
}
