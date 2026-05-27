<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wayfinder Paths
    |--------------------------------------------------------------------------
    |
    | Configure the output paths for Wayfinder-generated TypeScript files.
    | Routes directory contains named route functions.
    | Actions directory contains controller action functions.
    |
    */

    'paths' => [
        'routes' => resource_path('js/routes'),
        'actions' => resource_path('js/actions'),
    ],
];
