<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

final class Application extends Media implements MediaTypeInterface
{
    // https://www.phpliveregex.com/p/MxF
    const MASK = '/^[\d{2,3}]*(.*)[\.|\-](.*)\..*$/i';

    // https://www.phpliveregex.com/p/MxG
    const VERSION_MASK = '/^([A-Za-z\.]+)\.((v|version|\d{1,})[0-9\.]*)\..*$/i';

    const RELEASE_TYPES = [
        'update',
        'updated',
        'hotfix',
        'dlc',
        'keygen',
    ];

    /**
     * Title of the Application.
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
        if (2 > count($this->matches)) return;

        $titleWords = [];
        $release = '';
        [, $applicationStr ] = $this->matches;
        $cleaned = $applicationStr;

        // Find the version string and chop it from $applicationStr
        $version = $this->getVersionFromApplicationStr($applicationStr);
        if ('' !== $version) {
            $i = strpos($applicationStr, $version);
            if ($i !== false) {
                $cleaned = substr($applicationStr, 0, $i);
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

        $this->title = $this->formatTitle(implode(' ', $titleWords));
        $this->version = $version;
        $this->release = $release;
        $this->tags = $this->formatTags($applicationStr);
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
     * @param string $applicationStr
     * @return string|null
     */
    private function getVersionFromApplicationStr(string $applicationStr): string|null
    {
        $matches = [];
        $matchResult = preg_match(self::VERSION_MASK, $applicationStr, $matches, PREG_UNMATCHED_AS_NULL);
        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match media version to the metadata.");
        }

        if (3 > count($matches)) return null;

        return $matches[2];
    }

}
