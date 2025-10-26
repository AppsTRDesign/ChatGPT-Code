<?php
return [
    'base_url' => 'https://fileupload.noasoft.org',
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'fileupload_db',
        'user' => 'db_user',
        'pass' => 'db_password',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'max_file_size' => 50 * 1024 * 1024, // 50 MB
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'text/plain', 'application/zip',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ],
    ],
    'auth' => [
        'username' => 'admin',
        // Hash generated with password_hash('change-this-password', PASSWORD_DEFAULT)
        'password_hash' => '$2y$10$T87xJt54x7qs/Xxu.AwYju5z7YltArlONfxWozTe5ZTCDxbFzNw2e',
    ],
];
