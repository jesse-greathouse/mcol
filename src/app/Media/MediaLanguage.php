<?php

namespace App\Media;

final class MediaLanguage
{
    const CHINESE = 'chinese';
    const FRENCH = 'french';
    const GERMAN = 'german';
    const JAPANESE = 'japanese';
    const KOREAN = 'korean';

    /**
     * Returns a list of a all the media languages.
     *
     * @return array
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

    public static function getExpandedLanguages(): array
    {
        return [
            self::KOREAN => [
                'korsub',
            ],
        ];
    }

}
