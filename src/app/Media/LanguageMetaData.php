<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait LanguageMetaData
{
    /** @var array<string> $expandedLanguages */
    private array $expandedLanguages = [];

    /**
     * Returns a language value extracted from the file name.
     *
     * This method attempts to match a language pattern from the provided file name.
     * If no match is found, an exception is thrown.
     *
     * @param string $fileName The file name to extract language from.
     * @return string The matched language in lowercase, or an empty string if no match.
     * @throws MediaMetadataUnableToMatchException If the file name does not match the expected language pattern.
     */
    public function getLanguage(string $fileName): string
    {
        // Get the regex mask to match languages
        $languageMask = $this->getLanguageMask();

        // Perform regex match
        if (preg_match($languageMask, $fileName, $matches)) {
            return strtolower($matches[0]);
        }

        // If no match, throw exception
        throw new MediaMetadataUnableToMatchException("Unable to match language metadata for: $fileName.");
    }

    /**
     * Generates and returns a regular expression mask for all supported languages.
     *
     * This method dynamically generates a regex pattern based on the languages
     * defined in the MediaLanguage class and any expanded languages.
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

        // Return the compiled regex pattern
        return '/^(' . implode('|', $this->expandedLanguages) . ')$/i';
    }
}
