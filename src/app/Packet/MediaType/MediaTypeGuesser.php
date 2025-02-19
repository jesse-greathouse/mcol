<?php

namespace App\Packet\MediaType;

use App\Media\MediaType;

/**
 * Class MediaTypeGuesser
 * Attempts to guess the media type based on a given file name using regular expressions.
 *
 * @package App\Packet\MediaType
 */
class MediaTypeGuesser
{
    /** @var string Regular expression for matching episode names (e.g., S01E01). */
    const EPISODE_MASK = '/(\.|\-|\s)S\d{2}E\d{2}(\.|\-|\s)/is';

    /** @var string Regular expression for matching episode names by date. */
    const EPISODE_BY_DATE_MASK = '/(\.|\-|\s)\d{2,4}(\.|\-|\s)\d{2}(\.|\-|\s)\d{2}(\.|\-|\s)/is';

    /** @var string Regular expression for matching season names (e.g., S01). */
    const SEASON_MASK = '/(\.|\-|\s)S\d{2}(\.|\-|\s)/is';

    /** @var string Regular expression for matching multiple season names (e.g., S01-S02). */
    const MULTIPLE_SEASON_MASK = '/(\.|\-|\s)S\d{2}\-S\d{2}(\.|\-|\s)/is';

    /** @var string Regular expression for matching porn-related names. */
    const PORN_MASK = '/(\.|\-|\s)XXX(\.|\-|\s)/is';

    /** @var string Regular expression for matching movie formats or genres. */
    const MOVIE_MASK = '/720|1080|2160|UHD|BluRay|BDRip|DVDRip|WEBRiP|Anime|WEB\-DL|h\.264|x\.264|h264|x264|h\.265|h265|x265|XviD/is';

    /** @var string Regular expression for matching book-related names. */
    const BOOK_MASK = '/AUDIOBOOK|ABOOK|eBOOK|ePUB|BOOKWARE-SCHOLASTiC|\.pdf|iLLiTERATE|iMPART|iLEARN|XQZT\.tar|JGTiSO|KNiSO|NOGRP/is';

    /** @var string Regular expression for matching music-related names. */
    const MUSIC_MASK = '/MP3|FLAC|Discography|TosK|MOD\.tar|ENViED|AFO\.tar|KzT\.tar|wAx\.tar|JUSTiFY\.tar|UME\.tar/is';

    /** @var string Regular expression for matching game-related names. */
    const GAME_MASK = '/(?:^(?!.*mkv|.*mp4|.*avi|.*m4v).*?)(ROM|SNES|NSW|PC\.tar|macOS\.tar|SKIDROW|VENOM|SUXXORS|GOG|CODEX|DOGE|RAZOR|TENOKE|PLAZA|TiNYiSO|FLT|EMPRESS|RELOADED|FitGirl|PROPHET|HOODLUM|ElAmigos|RIDDICK|DINOByTES|ELiTE|RUNE|ENRiCH|KaOs\.tar|Sam2k8|I_KnoW|CPY\.iso|DELUSIONAL|PLAYMAGiC)/is';

    /** @var string Regular expression for matching application-related names. */
    const APPLICATION_MASK = '/WIN|x64|64\.bit|net\.tar|PATCH|MULTILINGUAL|MAGNiTUDE|Keygen|TiMES|macOS|m0nkrus|TNT|Senftube|P2P|SSQ|BLZiSO|Setup\.tar|with\.Crack|PRO\./is';

    /** @var string Regular expression for matching application version information. */
    const APPLICATION_VERSION_MASK = '/\.v[0-9]+[\.0-9]*/i';

    /**
     * The file name to be analyzed.
     *
     * @var string
     */
    protected string $fileName;

    /**
     * MediaTypeGuesser constructor.
     *
     * @param string $fileName The name of the file to guess the media type for.
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Attempts to guess and return the media type based on the file name.
     *
     * @return string|null The guessed media type, or null if no match is found.
     */
    public function guess(): ?string
    {
        $patterns = [
            self::EPISODE_MASK => MediaType::TV_EPISODE,
            self::EPISODE_BY_DATE_MASK => MediaType::TV_EPISODE,
            self::SEASON_MASK => MediaType::TV_SEASON,
            self::MULTIPLE_SEASON_MASK => MediaType::TV_SEASON,
            self::PORN_MASK => MediaType::PORN,
            self::MOVIE_MASK => MediaType::MOVIE,
            self::BOOK_MASK => MediaType::BOOK,
            self::MUSIC_MASK => MediaType::MUSIC,
            self::GAME_MASK => MediaType::GAME,
            self::APPLICATION_MASK => MediaType::APPLICATION,
            self::APPLICATION_VERSION_MASK => MediaType::APPLICATION,
        ];

        foreach ($patterns as $mask => $mediaType) {
            if ($this->hasMatch($mask)) {
                return $mediaType;
            }
        }

        return null;
    }

    /**
     * Performs regex matching to see if the file name fits the provided pattern.
     *
     * @param string $mask The regex pattern to match against the file name.
     * @return bool True if a match is found, false otherwise.
     */
    protected function hasMatch(string $mask): bool
    {
        $result = preg_match($mask, $this->fileName);
        return $result === 1;  // True if there is a match (preg_match returns 1), false otherwise (including errors)
    }
}
