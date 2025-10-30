<?php
return [
    'app_name' => 'NoaSoft QR Menu',
    'base_url' => 'https://qrmenu.noasoft.org',
    'timezone' => 'Europe/Istanbul',
    'env' => 'production',
    'debug' => false,
    'database' => [
        'host' => 'localhost',
        'name' => 'qrmenu',
        'user' => 'qrmenu_user',
        'pass' => 'qrmenu_pass',
        'charset' => 'utf8mb4',
    ],
    'jwt_secret' => 'change_this_secret_in_production',
    'upload' => [
        'directory' => __DIR__ . '/../../public/uploads',
        'base_url' => '/public/uploads',
        'max_size' => 3 * 1024 * 1024,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp']
    ],
    'api' => [
        'socket_server' => 'https://qrmenu.noasoft.org:4000/notify',
        'socket_client' => 'https://qrmenu.noasoft.org:4000',
        'qr_api' => 'https://qrcode.noasoft.org/api/v1/qr'
    ]
];
