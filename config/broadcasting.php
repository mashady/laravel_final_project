<?php

use Illuminate\Broadcasting\Channel;

return [
    'default' => env('BROADCAST_DRIVER', 'log'),

'connections' => [
    'log' => [
        'driver' => 'log',
    ],

    'null' => [
        'driver' => 'null',
    ],
],

'channels' => [
    'default' => [
        'driver' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ],
    ],
],
];