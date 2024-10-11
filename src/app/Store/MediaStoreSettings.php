<?php

namespace App\Store;

use App\Store\Data;

final class MediaStoreSettings extends Data
{
    const URI = 'settings' . DIRECTORY_SEPARATOR . 'media-store.yml';

    const MOVIES_KEY = 'movies';
    const TV_KEY = 'tv';
    const BOOK_KEY = 'books';
    const MUSIC_KEY = 'music';
    const GAME_KEY = 'games';
    const APPLICATION_KEY = 'applications';

    /**
     * The body of data values that can be stored and retrieved.
     *
     * @var array
     */
    protected array $storable = [
        self::MOVIES_KEY        => [],
        self::TV_KEY            => [],
        self::BOOK_KEY          => [],
        self::MUSIC_KEY         => [],
        self::GAME_KEY          => [],
        self::APPLICATION_KEY   => [],
    ];

    /**
     * @param string $varDir The root where application data is stored.
     * @param ?array $config
     */
    public function __construct(string $varDir, $config =[])
    {
        $uri = $varDir . DIRECTORY_SEPARATOR . self::URI;
        parent::__construct($uri, $config);
    }
}
