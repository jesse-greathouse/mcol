<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait ExtensionMetaData
{
    // https://www.phpliveregex.com/p/MDB
    const EXTENSION_MASK = '/^(.*)\.(.*)$/s';

    /**
     * Returns the extension from a fileName.
     *
     * @param string $fileName
     * @return string|null
     */
    public function getExtension(string $fileName): string|null
    {
        $matches = [];

        $matchResult = preg_match(self::EXTENSION_MASK, $fileName, $matches, PREG_UNMATCHED_AS_NULL);

        if (false === $matchResult) {
            throw new MediaMetadataUnableToMatchException("Unable to match extension metadata for: $fileName.");
        }

        if (3 > count($matches)) return null;

        [, , $extension] = $matches;

        // Truncate extension at 8 characters so it fits in the database.
        return substr($extension, 0, 8);
    }
}
