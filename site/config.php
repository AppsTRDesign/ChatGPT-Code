<?php
return [
    'site' => [
        'name' => 'NoaSoft Converter',
        'brand' => 'NoaSoft Converter',
        'brand_html' => 'Noa<span>Soft</span> Converter',
        'base_url' => '',
        'noreply_email' => 'noreply@game.noasoft.org',
        'links' => [
            'home' => '/',
            'audio' => '/ses-donusturucu.php',
            'video' => '/video-donusturucu.php',
            'image' => '/gorsel-donusturucu.php',
            'faq' => '/sss.php',
            'contact' => '/iletisim.php',
            'copyright' => '/telif-haklari.php'
        ]
    ],
    'upload' => [
        'max_files' => 5,
        'max_size_mb' => 2048
    ],
    'paths' => [
        'storage' => __DIR__ . '/storage'
    ],
    'mail' => [
        'to' => 'destek@noasoft.org',
        'from' => 'NoaSoft Converter <noreply@game.noasoft.org>',
        'smtp' => [
            'enabled' => false,
            'host' => '',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls'
        ],
        'headers' => [
            'Content-Type: text/plain; charset=UTF-8'
        ]
    ]
];
