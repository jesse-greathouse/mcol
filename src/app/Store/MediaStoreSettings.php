<?php

namespace App\Store;

final class MediaStoreSettings extends Data
{
    const FILE = 'settings'.DIRECTORY_SEPARATOR.'media-store.yml';

    const MOVIES_KEY = 'movies';

    const TV_KEY = 'tv';

    const BOOK_KEY = 'books';

    const MUSIC_KEY = 'music';

    const GAME_KEY = 'games';

    const APPLICATION_KEY = 'applications';

    const XXX_KEY = 'xxx';

    /**
     * The body of data values that can be stored and retrieved.
     */
    protected array $storable = [
        self::MOVIES_KEY => [],
        self::TV_KEY => [],
        self::BOOK_KEY => [],
        self::MUSIC_KEY => [],
        self::GAME_KEY => [],
        self::APPLICATION_KEY => [],
        self::XXX_KEY => [],
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
