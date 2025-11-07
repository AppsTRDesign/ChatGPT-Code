<?php
return [
    'app' => [
        'name' => 'Telegram Automation',
        'env' => 'production',
        'debug' => false,
        'key' => null,
        'base_url' => 'https://telegrambot.noasoft.org',
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'telegrambot',
        'username' => 'your_db_user',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'mail' => [
        'host' => 'localhost',
        'port' => 25,
        'username' => '',
        'password' => '',
        'encryption' => '',
        'from_address' => 'bot@telegrambot.noasoft.org',
        'from_name' => 'Telegram Automation',
    ],
    'rate_limits' => [
        'global' => 60,
        'per_phone' => 30,
        'per_channel' => 15,
    ],
];
