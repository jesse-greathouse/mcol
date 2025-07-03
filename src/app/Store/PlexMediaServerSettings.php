<?php

namespace App\Store;

final class PlexMediaServerSettings extends Data
{
    const FILE = 'settings'.DIRECTORY_SEPARATOR.'plex-media-server.yml';

    const HOST = 'host';

    const TOKEN = 'token';

    /**
     * The body of data values that can be stored and retrieved.
     */
    protected array $storable = [
        self::HOST => null,
        self::TOKEN => null,
    ];

    /**
     * @param  string  $path  The root where settings data is stored.
     * @param  ?array  $config
     */
    public function __construct(string $path, $config = [])
    {
        $uri = $path.DIRECTORY_SEPARATOR.self::FILE;
        parent::__construct($uri, $config);
    }
}
