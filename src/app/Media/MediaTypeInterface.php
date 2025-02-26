<?php

namespace App\Media;

/**
 * Interface for handling media types.
 * Defines the structure for media type classes to implement.
 */
interface MediaTypeInterface
{
    /**
     * Converts the media type to an associative array.
     *
     * @return array The media type as an array.
     */
    public function toArray(): array;

    /**
     * Matches the media metadata from the file name.
     *
     * @param string $fileName the name of the file with which to perform the data extraction.
     * @return void.
     */
    public function match(string $fileName): void;

    /**
     * Maps the media type to a specific transformation or logic.
     *
     * @return void
     */
    public function map(): void;
}
