<?php
return [
    'app_name' => 'NoaSoft QR Menu',
    'base_url' => 'https://qrmenu.noasoft.org',
    'timezone' => 'Europe/Istanbul',
    'env' => 'production',
    'debug' => false,
    'jwt_secret' => 'change_this_secret_in_production',
    'upload' => [
        'cloudinary_url' => 'https://api.cloudinary.com/v1_1/YOUR_CLOUD_NAME/image/upload',
        'api_key' => 'CLOUDINARY_API_KEY',
        'api_secret' => 'CLOUDINARY_API_SECRET',
        'preset' => 'unsigned_preset'
    ],
    'api' => [
        'socket_server' => 'http://127.0.0.1:4000/notify',
        'qr_api' => 'https://qrcode.noasoft.org/api/v1/qr'
    ]
];
