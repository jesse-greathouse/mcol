<?php

namespace App\Media;

use Stringy\Stringy as S;

use App\Exceptions\MediaMetadataUnableToMatchException;

abstract class Media
{
    const TAG_MASK = '/(UHD|BluRay|BDRip|DVDRip|WEB|WEBRiP|Anime|WebDL|WEB\-DL|h\.264|x\.264|h264|x264|h\.265|h265|x265|XviD|MP3|CD|FLAC|ROM|SNES|NSW|MacOS|AMZN|NF|ROKU|TVING|HIST|PCOCK|HMAX|DSNP|ATVP|AV1|10\-bit|DD2\.0|DDP2\.0|DDP5\.1|DD5\.1|DDP7\.1|DD7\.1|DD|Opus|HEVC|HDTV|AAC|AAC2\.0|Atmos|DUBBED|DUAL|Remux|TrueHD|HDR|DV|DoVi|DTSHD|HYBRID|24BIT|16BIT|44kHz|x64)/i';

    /**
     * Undocumented variable.
     *
     * @var array
     */
    protected array $matches = [];

    /**
     * File  Name from which the metadata was derived.
     *
     * @var string
     */
    protected $fileName;

    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;
        $matchResult = preg_match($this->getMask(), $this->fileName, $this->matches, PREG_UNMATCHED_AS_NULL);
        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match media to the metadata for: $fileName.");
        }

        $this->map();
    }

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public abstract function map(): void;

    /**
     * Returns the mask with which to match the media.
     *
     * @return string
     */
    public abstract function getMask(): string;

    /**
     * Formats a title into a readable string.
     *
     * @param string $title
     * @return string
     */
    public function formatTitle(string $title): string
    {
        if ('' === $title) return $title;

        // Replace dots with spaces.
        $title = str_replace('.', ' ', $title);

        // Trim whitespace.
        $title = trim($title);

        // Make Title Case: "Harry Potter and the Half Blood Prince".
        $stringy = S::create($title);
        $title = $stringy->toTitleCase();

        return $title;
    }

    /**
     * Formats a string of tags into a list of tags.
     *
     * @param string $tagStr
     * @return array
     */
    public function formatTags(string $tagStr): array
    {
        $matches = [];
        $tags = [];

        if ('' === $tagStr) return $tags;

        $result = preg_match_all(self::TAG_MASK, $tagStr, $matches);

        if (false === $result) {
            throw new MediaMetadataUnableToMatchException("Unable to match Media metadata with: \"$tagStr\".");
        }

        if (isset($matches[1]) && 0 < $matches[1]) {
            $tags = $matches[1];
            // change all values to lower case
            array_walk($tags, fn(&$v) => $v = strtolower($v));
        }

        return $tags;
    }
}
