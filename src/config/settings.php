<?php

return [
    'path' =>  env('VAR', '/var/mcol'),
    'stores' => [
        'media_store' => [
            'class' => 'App\Store\MediaStoreSettings',
            'options' => [],
        ],
        'plex_media_server' => [
            'class' => 'App\Store\PlexMediaServerSettings',
            'options' => [],
        ],
    ],
];
