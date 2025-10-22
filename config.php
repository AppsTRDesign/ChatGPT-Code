<?php
return [
    'base_url' => getenv('APP_URL') ?: 'https://qrmenu.noasoft.org',
    'uploads_path' => __DIR__ . '/uploads',
    'cache_path' => __DIR__ . '/cache',
    'cache_ttl' => 300,
    'font_cache_path' => __DIR__ . '/cache/fonts',
    'invoice_font_url' => getenv('INVOICE_FONT_URL') ?: 'https://github.com/google/fonts/raw/main/ofl/notosans/NotoSans-Regular.ttf',
    'invoice_font_name' => getenv('INVOICE_FONT_NAME') ?: 'NotoSans',
    'order_sound_url' => getenv('ORDER_SOUND_URL') ?: 'https://cdn.pixabay.com/audio/2022/03/15/audio_50187b69f1.mp3',
    'waiter_sound_url' => getenv('WAITER_SOUND_URL') ?: 'https://cdn.pixabay.com/audio/2022/03/15/audio_50187b69f1.mp3',
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'qrmenu',
        'username' => getenv('DB_USER') ?: 'qrmenu',
        'password' => getenv('DB_PASSWORD') ?: 'qrmenu_pass',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_turkish_ci',
    ],
];
