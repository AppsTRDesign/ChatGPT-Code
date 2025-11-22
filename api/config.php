<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'autosurf',
    'db_user' => getenv('DB_USER') ?: 'autosurf_user',
    'db_pass' => getenv('DB_PASS') ?: 'change_me',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
    'initial_points' => 300,
];
