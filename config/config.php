<?php

return [
    'app_name' => 'NoaSoft QR Menü',
    'timezone' => 'Europe/Istanbul',
    'locale' => 'tr',
    'base_url' => 'https://qrmenu.noasoft.org',
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => 'qrmenu',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'qr_api' => [
        'base_url' => 'https://qrcode.noasoft.org/api/v1/qr',
    ],
    'storage' => [
        'settings' => __DIR__ . '/../storage/settings.json',
        'languages' => __DIR__ . '/../languages',
        'uploads' => __DIR__ . '/../storage/uploads',
    ],
];
