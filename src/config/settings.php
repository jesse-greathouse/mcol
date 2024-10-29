<?php

return [
    'path' =>  env('VAR', '/var/mcol'),
    'stores' => [
        'system' => [
            'class' => 'App\Store\SystemSettings',
        ],
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
