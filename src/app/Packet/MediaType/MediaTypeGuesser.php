<?php

namespace App\Packet\MediaType;

use App\Exceptions\MediaMatchException,
    App\Media\MediaType;

use ErrorException;

/**
 * Class MediaTypeGuesser
 * Attempts to guess the media type based on a given file name using regular expressions.
 *
 * @package App\Packet\MediaType
 */
class MediaTypeGuesser
{
    /** @var string Regular expression for matching episode names (e.g., S01E01, Season1Episode1, S-01E_01). */
    const EPISODE_MASK = '/(\.|\-|\s|_)(S|Se|Sn|Season)(\.|\-|\s|_)?\d{1,3}(\.|\-|\s|_)?(E|Ep|Epi|Episode)(\.|\-|\s|_)?\d{1,3}(\.|\-|\s|_)/is';

    /** @var string Regular expression for matching episode names by date. */
    const EPISODE_BY_DATE_MASK = '/(\.|\-|\s|_)\d{2,4}(\.|\-|\s|_)\d{2}(\.|\-|\s|_)\d{2}(\.|\-|\s|_)/is';

    /** @var string Regular expression for matching simple episode names by: 2x10. */
    const SIMPLE_EPISODE_MASK = '/^[\d{2}]*(.+)(?:\.|\-|\s|_)(?:S|Se|Sn|Season)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)?(?:E|Ep|Epi|Episode)(?:\.|\-|\s|_)?(\d{1,3})(?:\.|\-|\s|_)(.+)(?:\..*)$/is';

    /** @var string Regular expression for matching UFC event file names */
    const UFC_EPISODE_MASK = '/^((.*\bUFC\b.*)\W+(\d{1,})\W+(\w+\W+vs?\W+\w+))((.*)(480[p]?|720[p]?|1080[p]?|2160[p]?))?(.*)(?:\..*)/is';

    /** @var string Regular expression for matching anime TV episode filenames. */
    const ANIME_EPISODE_MASK = '/^(?:\[[^\]]+\]\s+)?([A-Za-z0-9\s]+)(?:\s+\-\s+)((\d{1,3})(?:\s+\-)?(\s+))?(?:\-\s+)?([^\(\[]+)?((?:[\(\[].*)?(480[p]?|720[p]?|1080[p]?|2160[p]?)(?:.*[\)\]])?)?(.*)?(\..*)$/is';

    /** @var string Regular expression for matching cartoon TV episode filenames. */
    const CARTOON_EPISODE_MASK = '/^([\w\s]+)\W+(\d{1,3})x(\d{1,3})\W+([\w\s]+)(?:\W+?)?(.*)?(?:\..*)$/is';

    /** @var string Regular expression for matching TV episode filenames with only the episiode number. */
    const ONLY_EPISODE_MASK = '/^[\d{2}]*(.*)[\.|\-|\s\_](?:E|Ep|Epi|Episode)(\d{1,3})[\.|\-|\s\_](480[p]?|720[p]?|1080[p]?|2160[p]?)?(.+)$/is';

    /** @var string Regular expression for matching season names (e.g., S01, Season1, Sn2). */
    const SEASON_MASK = '/(\.|\-|\s|_)(S|Se|Sn|Season)(\.|\-|\s|_)?\d{1,3}(\.|\-|\s|_)/is';

    /** @var string Regular expression for matching multiple season names (e.g., S01-S02). */
    const MULTIPLE_SEASON_MASK = '/(\.|\-|\s|_)(S|Se|Sn|Season)\d{1,3}(\.|\-|\s|_)(S|Se|Sn|Season)\d{1,3}(\.|\-|\s|_)/is';

    /** @var string Regular expression for matching porn-related names. */
    const PORN_MASK = '/(\.|\-|\s|_)XXX(\.|\-|\s|_)/is';

    /** @var string Regular expression for matching movie formats or genres. */
    const MOVIE_MASK = '/720|1080|2160|UHD|BluRay|BDRip|DVDRip|WEBRiP|Anime|WEB\-DL|h\.264|x\.264|h264|x264|h\.265|h265|x265|XviD|\.mkv$|\.mp4$/is';

    /** @var string Regular expression for matching book-related names. */
    const BOOK_MASK = '/\bAUDIOBOOK\b|\bABOOK\b|\beBOOK\b|\bePUB\b|\bBOOKWARE-SCHOLASTiC\b|\.pdf|\biLLiTERATE\b|\biMPART\b|\biLEARN\b|\bXQZT\.tar\b|\bJGTiSO\b|\bKNiSO\b|\bNOGRP\b/is';

    /** @var string Regular expression for matching music-related names. */
    const MUSIC_MASK = '/\bMP3\b|\bFLAC\b|\bDiscography\b|\bTosK\b|\bMOD\.tar\b|\bENViED\b|\bAFO\.tar\b|\bKzT\.tar\b|\bwAx\.tar\b|\bJUSTiFY\.tar\b|\bUME\.tar\b/is';

    const GAME_MASK = '/(?:^(?!.*\b(?:mkv|mp4|avi|m4v)\b).*?)'
    . '(\bROM\b|\bSNES\b|\bNSW\b|\bPC\.tar\b|\bmacOS\.tar\b|\bSKIDROW\b|\bVENOM\b|\bSUXXORS\b|\bGOG\b|\bCODEX\b|\bDOGE\b|\bRAZOR\b|\bTENOKE\b|\bPLAZA\b|\bTiNYiSO\b|\bFLT\b|\bEMPRESS\b|\bRELOADED\b|\bFitGirl\b|\bPROPHET\b|\bHOODLUM\b|\bElAmigos\b|\bRIDDICK\b|\bDINOByTES\b|\bELiTE\b|\bRUNE\b|\bENRiCH\b|\bKaOs\.tar\b|\bSam2k8\b|\bI_KnoW\b|\bCPY\.iso\b|\bDELUSIONAL\b|\bPLAYMAGiC\b)'
    . '/is';

    /** @var string Regular expression for matching application-related names. */
    const APPLICATION_MASK = '/\bAUTODESK\b|\bWIN\b|\bx64\b|\b64\.bit\b|\bnet\.tar\b|\bPATCH\b|\bMULTILINGUAL\b|\bMAGNiTUDE\b|\bKeygen\b|\bTiMES\b|\bmacOS\b|\bm0nkrus\b|\bTNT\b|\bSenftube\b|\bP2P\b|\bSSQ\b|\bBLZiSO\b|\bSetup\.tar\b|\bwith\.Crack\b|\bPRO\./is';

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
            self::SIMPLE_EPISODE_MASK => MediaType::TV_EPISODE,
            self::UFC_EPISODE_MASK => MediaType::TV_EPISODE,
            self::ANIME_EPISODE_MASK => MediaType::TV_EPISODE,
            self::CARTOON_EPISODE_MASK => MediaType::TV_EPISODE,
            self::ONLY_EPISODE_MASK => MediaType::TV_EPISODE,
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
     * @throws RuntimeException If the regex compilation fails.
     */
    protected function hasMatch(string $mask): bool
    {
        try {
            $result = preg_match($mask, $this->fileName);
            return $result === 1; // True if a match is found, false otherwise
        } catch (ErrorException $e) {
            $message = sprintf("Media Match failed for pattern: %s\nFile name: %s\nError: %s",
                $mask,
                $this->fileName,
                $e->getMessage()
            );

            throw new MediaMatchException($message);
        }
    }
}
