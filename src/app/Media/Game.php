<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

final class Game extends Media implements MediaTypeInterface
{
    // https://www.phpliveregex.com/p/MxA
    const MASK = '/^[\d{2,3}]*(.*)[\.|\-](.*)\..*$/i';

    // https://www.phpliveregex.com/p/MxD
    const VERSION_MASK = '/^(.*).*((v|version)\d{1,}[A-Za-z0-9\.\-]*)$/i';

    const RELEASE_TYPES = [
        'update',
        'updated',
        'hotfix',
        'dlc',
    ];

    /**
     * Title of the Game.
     *
     * @var string
     */
    private $title;

    /**
     * Artist of the album.
     *
     * @var string
     */
    private $version;

    /**
     * Type of release Update, DLC, Hotfix, etc...
     *
     * @var string
     */
    private $release;

    /**
     * List of strings that describe various features of the media.
     *
     * @var array<string>
     */
    private array $tags = [];

    /**
     * Maps the result of match to properties.
     *
     * @return void
     */
    public function map(): void
    {
        if (1 > count($this->matches)) return;

        $titleWords = [];
        $release = '';
        [, $gameStr ] = $this->matches;
        $gameStr = (null === $gameStr) ? '' : trim($gameStr);

        $cleaned = $gameStr;
        // Find the version string and chop it from $gameStr
        $version = $this->getVersionFromGameStr($gameStr);
        if ('' !== $version && null !== $version) {
            $i = strpos($gameStr, $version);
            if ($i !== false) {
                $cleaned = substr($gameStr, 0, $i);
            }
        }

        // Sometimes they're separated by underscores, other times by periods...
        $separator = (false === strpos($cleaned, '.')) ? '_' : '.';
        $parts = explode($separator, $cleaned);

        // Extract the release types from the title.
        forEach($parts as $word) {
            $word = strtolower($word);
            if (in_array($word, self::RELEASE_TYPES)) {
                $release = $word;
                break;
            } else {
                $titleWords[] = $word;
            }
        }

        $title = (0 < count($titleWords)) ? implode(' ', $titleWords) : '';

        $this->title = $this->formatTitle($title);
        $this->version = $version;
        $this->release = $release;
        $this->tags = $this->formatTags($gameStr);
    }

    /**
     * Returns the mask with which to match the media.
     *
     * @return string
     */
    public function getMask(): string
    {
        return self::MASK;
    }

    /**
     * Returns the object as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'     => $this->title,
            'version'   => $this->version,
            'release'   => $this->release,
            'tags'      => $this->tags,
        ];
    }

    /**
     * Undocumented function
     *
     * @param string $gameStr
     * @return string|null
     */
    private function getVersionFromGameStr(string $gameStr): string|null
    {
        $matches = [];
        $matchResult = preg_match(self::VERSION_MASK, $gameStr, $matches, PREG_UNMATCHED_AS_NULL);
        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match media version to the metadata.");
        }

        if (3 > count($matches)) return null;

        return $matches[2];
    }

}
