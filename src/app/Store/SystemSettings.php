<?php

namespace App\Store;

/**
 * SystemSettings works differently than all the other stores.
 * The values of this settings store is dictated by the system.
 * Values will not be written or saved.
 * Values statically surface system properties.
 */
final class SystemSettings extends Data
{
    const DIRECTORY_SEPARATOR = 'DIRECTORY_SEPARATOR';

    // DS is just shorthand for DIRECTORY_SEPARATOR
    const DS = 'DS';

    /**
     * The body of data values that can be stored and retrieved.
     */
    protected array $storable = [
        self::DS => DIRECTORY_SEPARATOR,
        self::DIRECTORY_SEPARATOR => DIRECTORY_SEPARATOR,
    ];

    /**
     * Just Overloads Construct
     */
    public function __construct() {}

    /**
     * Just overloads save method.
     */
    public function save(): void
    {
        // Do nothing
    }
}
