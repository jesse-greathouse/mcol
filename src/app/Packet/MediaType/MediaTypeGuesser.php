<?php

namespace App\Packet\MediaType;

use App\Media\MediaType;

class MediaTypeGuesser
{
    const EPISODE_MASK = '/(\.|\-|\s)S\d{2}E\d{2}(\.|\-|\s)/is';

    const EPISODE_BY_DATE_MASK = '/(\.|\-|\s)\d{2,4}(\.|\-|\s)\d{2}(\.|\-|\s)\d{2}(\.|\-|\s)/is';

    const SEASON_MASK = '/(\.|\-|\s)S\d{2}(\.|\-|\s)/is';

    const MULTIPLE_SEASON_MASK = '/(\.|\-|\s)S\d{2}\-S\d{2}(\.|\-|\s)/is';

    const PORN_MASK = '/(\.|\-|\s)XXX(\.|\-|\s)/is';

    const MOVIE_MASK = '/720|1080|2160|UHD|BluRay|BDRip|DVDRip|WEBRiP|Anime|WEB\-DL|h\.264|x\.264|h264|x264|h\.265|h265|x265|XviD/is';

    const BOOK_MASK = '/AUDIOBOOK|ABOOK|eBOOK|ePUB|BOOKWARE-SCHOLASTiC|\.pdf|iLLiTERATE|iMPART|iLEARN|XQZT\.tar|JGTiSO|KNiSO|NOGRP/is';

    const MUSIC_MASK = '/MP3|FLAC|Discography|TosK|MOD\.tar|ENViED|AFO\.tar|KzT\.tar|wAx\.tar|JUSTiFY\.tar|UME\.tar/is';

    // https://www.phpliveregex.com/p/MDw
    const GAME_MASK = '/(?:^(?!.*mkv|.*mp4|.*avi|.*m4v).*?)(ROM|SNES|NSW|PC\.tar|macOS\.tar|SKIDROW|VENOM|SUXXORS|GOG|CODEX|DOGE|RAZOR|TENOKE|PLAZA|TiNYiSO|FLT|EMPRESS|RELOADED|FitGirl|PROPHET|HOODLUM|ElAmigos|RIDDICK|DINOByTES|ELiTE|RUNE|ENRiCH|KaOs\.tar|Sam2k8|I_KnoW|CPY\.iso|DELUSIONAL|PLAYMAGiC)/is';

    const APPLICATION_MASK = '/WIN|x64|64\.bit|net\.tar|PATCH|MULTILINGUAL|MAGNiTUDE|Keygen|TiMES|macOS|m0nkrus|TNT|Senftube|P2P|SSQ|BLZiSO|Setup\.tar|with\.Crack|PRO\./is';

    const APPLICATION_VERSION_MASK = '/\.v[0-9]+[\.0-9]*/i';

    /**
     * File name given for guessing the media type.
     *
     * @var string
     */
    protected string $fileName;

    /**
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Attempts to guess and returns the media type.
     *
     * @return string|null
     */
    public function guess(): string|null
    {
        if ($this->hasMatch(self::EPISODE_MASK)) {
            return MediaType::TV_EPISODE;
        }

        if ($this->hasMatch(self::EPISODE_BY_DATE_MASK)) {
            return MediaType::TV_EPISODE;
        }

        if ($this->hasMatch(self::SEASON_MASK)) {
            return MediaType::TV_SEASON;
        }

        if ($this->hasMatch(self::MULTIPLE_SEASON_MASK)) {
            return MediaType::TV_SEASON;
        }

        if ($this->hasMatch(self::PORN_MASK)) {
            return MediaType::PORN;
        }

        if ($this->hasMatch(self::MOVIE_MASK)) {
            return MediaType::MOVIE;
        }

        if ($this->hasMatch(self::BOOK_MASK)) {
            return MediaType::BOOK;
        }

        if ($this->hasMatch(self::MUSIC_MASK)) {
            return MediaType::MUSIC;
        }

        if ($this->hasMatch(self::GAME_MASK)) {
            return MediaType::GAME;
        }

        if ($this->hasMatch(self::APPLICATION_MASK)) {
            return MediaType::APPLICATION;
        }

        if ($this->hasMatch(self::APPLICATION_VERSION_MASK)) {
            return MediaType::APPLICATION;
        }

        return null;
    }

    /**
     * Performs the regex and returns a boolean indicating a match.
     *
     * @param string $mask
     * @return boolean
     */
    protected function hasMatch(string $mask):bool
    {
        $matches = [];
        preg_match($mask, $this->fileName, $matches);

        if (0 < count($matches)) {
            return true;
        }

        return false;
    }

}
