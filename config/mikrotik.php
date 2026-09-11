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
        '1h' => env('MIKROTIK_PROFILE_1H', '10mbps-profile'),
        '4h' => env('MIKROTIK_PROFILE_4H', '5mbps-profile'),
    ],
    'connector' => [
        'enabled' => (bool) env('MIKROTIK_CONNECTOR_ENABLED', true),
        'protocol' => env('MIKROTIK_CONNECTOR_PROTOCOL', 'supportportal-connect'),
        'connection_name' => env('MIKROTIK_CONNECTOR_CONNECTION_NAME', 'Broadband Connection'),
        'token_ttl_seconds' => (int) env('MIKROTIK_CONNECTOR_TOKEN_TTL', 120),
        'verification_window_seconds' => (int) env('MIKROTIK_CONNECTOR_VERIFICATION_WINDOW', 300),
        'bind_token_to_ip' => (bool) env('MIKROTIK_CONNECTOR_BIND_TOKEN_TO_IP', true),
        'require_https' => (bool) env('MIKROTIK_CONNECTOR_REQUIRE_HTTPS', true),
    ],
];
