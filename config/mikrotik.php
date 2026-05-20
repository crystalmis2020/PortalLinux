<?php

return [
    'host' => env('MIKROTIK_HOST'),
    'port' => (int) env('MIKROTIK_PORT', 8728),
    'username' => env('MIKROTIK_USERNAME'),
    'password' => env('MIKROTIK_PASSWORD'),
    'timeout' => (int) env('MIKROTIK_TIMEOUT', 10),
    'enabled' => (bool) env('MIKROTIK_ENABLED', false),
    'default_customer' => env('MIKROTIK_CUSTOMER', 'xonivre'),
    'service' => env('MIKROTIK_SERVICE', 'pppoe'),
    'profiles' => [
        '1h' => env('MIKROTIK_PROFILE_1H', '1MB_Connection'),
        '2h' => env('MIKROTIK_PROFILE_2H', '5MB_Connection'),
        '3h' => env('MIKROTIK_PROFILE_3H', '5MB_Connection'),
        '8h' => env('MIKROTIK_PROFILE_8H', '50MB_Connection'),
    ],
];
