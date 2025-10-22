<?php
return [
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: 'https://muhasebe.noasoft.org',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'database' => getenv('DB_DATABASE') ?: 'muhasebe',
        'user' => getenv('DB_USER') ?: 'muhasebe_user',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
    ],
];
