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
     * Retrieves the associated mask as a string.
     *
     * @return string The mask.
     */
    public function getMask(): string;

    /**
     * Maps the media type to a specific transformation or logic.
     *
     * @return void
     */
    public function map(): void;
}
