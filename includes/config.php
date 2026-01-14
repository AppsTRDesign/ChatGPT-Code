<?php

return [
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'cicek',
        'username' => getenv('DB_USER') ?: 'cicek_user',
        'password' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => getenv('APP_URL') ?: 'https://cicek.noasoft.org',
        'timezone' => 'Europe/Istanbul',
    ],
];
