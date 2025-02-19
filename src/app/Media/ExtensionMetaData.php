<?php

namespace App\Media;

use App\Exceptions\MediaMetadataUnableToMatchException;

trait ExtensionMetaData
{
    /**
     * Extracts the file extension from the given filename.
     *
     * This method splits the filename by periods (.) and retrieves the last part,
     * which is considered the extension. The extension is truncated to a maximum of
     * 8 characters to ensure it fits within the database limits.
     *
     * @param string $fileName The full file name (e.g., 'example.txt').
     * @return string The file extension, truncated to a maximum of 8 characters.
     * @throws MediaMetadataUnableToMatchException If the filename doesn't have an extension.
     */
    public function getExtension(string $fileName): string
    {
        // Split the filename by periods (.)
        $parts = explode('.', $fileName);

        // If the filename doesn't contain a period or there are no parts after it, throw an exception.
        if (count($parts) < 2) {
            throw new MediaMetadataUnableToMatchException("Unable to match extension metadata for: $fileName.");
        }

        // Get the last part of the split string as the extension.
        $extension = end($parts);

        // Truncate the extension at 8 characters to fit the database requirement.
        return substr($extension, 0, 8);
    }
}
