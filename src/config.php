<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'database' => getenv('DB_NAME') ?: 'abbkfute_sma',
        'user' => getenv('DB_USER') ?: 'abbkfute_sma',
        'password' => getenv('DB_PASSWORD') ?: 'Rcis123$..',
    ],
    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'change-me-smart-masjid',
        'issuer' => 'smart-masjid',
        'access_ttl' => 3600, // seconds
        'refresh_ttl' => 2592000, // 30 days
    ],
];
