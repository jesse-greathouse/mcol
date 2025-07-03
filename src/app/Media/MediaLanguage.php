<?php

namespace App\Media;

/**
 * Class MediaLanguage
 *
 * Provides constants and methods related to supported media languages.
 * This class serves as a reference for media language codes and offers utility methods
 * to retrieve lists of languages and their expanded variants.
 */
final class MediaLanguage
{
    // Language constants
    const CHINESE = 'chinese';

    const FRENCH = 'french';

    const GERMAN = 'german';

    const JAPANESE = 'japanese';

    const KOREAN = 'korean';

    /**
     * Retrieves a list of all available media languages.
     *
     * @return string[] A list of media language codes.
     */
    public static function getMediaLanguages(): array
    {
        return [
            self::CHINESE,
            self::FRENCH,
            self::GERMAN,
            self::JAPANESE,
            self::KOREAN,
        ];
    }

    /**
     * Retrieves a list of expanded media languages, mapping a language to its variant.
     *
     * @return array<string, string[]> An associative array mapping a language to its expanded variant(s).
     */
    public static function getExpandedLanguages(): array
    {
        return [
            self::KOREAN => [
                'korsub', // Korean subtitle variant
            ],
        ];
    }
}
