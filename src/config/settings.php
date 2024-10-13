<?php

return [
    'path' =>  env('VAR', '/var/mcol') . DIRECTORY_SEPARATOR . 'settings',
    'stores' => [
        'media_store' => [
            'class' => 'App\Store\MediaStoreSettings',
            'options' => [],
        ],
    ],
];
