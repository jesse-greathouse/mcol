<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait LanguageMetaData
{
    /** @var array<string> */
    private array $expandedLanguages = [];

    /**
     * Returns a language value extracted from the file name.
     * Returns an empty string if no match is found.
     * Throws an exception if preg_match fails.
     *
     * @param  string  $fileName  The file name to extract language from.
     * @return string The matched language in lowercase, or an empty string if no match.
     *
     * @throws MediaMetadataUnableToMatchException If preg_match fails.
     */
    public function getLanguage(string $fileName): string
    {
        $language = $this->matchLanguage($fileName);

        // If no match, return an empty string.
        return $language === false ? '' : $language;
    }

    /**
     * Attempts to match the file name with a supported language.
     * Returns the language if matched, or false if no match.
     * Throws an exception if preg_match fails.
     *
     * @return string|false The matched language in lowercase, or false if no match.
     *
     * @throws MediaMetadataUnableToMatchException
     */
    private function matchLanguage(string $fileName)
    {
        // Get the regex mask to match languages
        $languageMask = $this->getLanguageMask();

        // Perform regex match
        $result = preg_match($languageMask, $fileName, $matches);

        if ($result === false) {
            throw new MediaMetadataUnableToMatchException("Preg_match failed when checking language metadata for: $fileName.");
        }

        // Return matched language or false if no match
        return $result === 1 ? strtolower($matches[0]) : false;
    }

    /**
     * Generates and returns a regular expression mask for all supported languages.
     *
     * @return string The regex pattern that matches any supported language.
     */
    public function getLanguageMask(): string
    {
        // Load expanded languages once and store for reuse
        if (empty($this->expandedLanguages)) {
            $languages = MediaLanguage::getMediaLanguages();
            foreach (MediaLanguage::getExpandedLanguages() as $language => $list) {
                $languages = array_merge($languages, $list);
            }

            // Store the merged language list for future reuse
            $this->expandedLanguages = $languages;
        }

        // Escape special characters and compile the regex pattern
        $escapedLanguages = array_map('preg_quote', $this->expandedLanguages);

        // Ensure the language is surrounded by dots, spaces, dashes, or underscores
        return '/(?<=^|[.\s\-_])('.implode('|', $escapedLanguages).')(?=[.\s\-_]|$)/i';
    }
}
