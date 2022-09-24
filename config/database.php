<?php

$uri = 'mongodb://';

if(env('DB_DNS_SEED_ENABLE', false))
{
    $uri='mongodb+srv://';
}

return [
    'default' => env('DB_CONNECTION', 'mongodb'),

    'connections' => [
        'mongodb' => [
            'driver' => 'mongodb',
            'dsn'=>$uri.env('DB_USERNAME', 'admin').':'.env('DB_PASSWORD', '').'@'.env('DB_HOST', 'localhost').'/',
            'database' => env('DB_DATABASE', 'jikan'),
        ]
    ],

    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0
        ]
    ],

    'migrations' => 'migrations'
];
