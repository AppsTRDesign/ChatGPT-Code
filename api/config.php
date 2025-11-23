<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'autosurf',
    'db_user' => getenv('DB_USER') ?: 'autosurf_user',
    'db_pass' => getenv('DB_PASS') ?: 'change_me',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
    'initial_points' => 300,
    'contact_email' => getenv('CONTACT_EMAIL') ?: 'info@noasoft.org',
    'google_tasks_enabled' => getenv('GOOGLE_TASKS_ENABLED') === '0' ? 0 : 1,
    'youtube_tasks_enabled' => getenv('YOUTUBE_TASKS_ENABLED') === '0' ? 0 : 1,
    'ad_banner_html' => getenv('AD_BANNER_HTML')
        ?: '<a href="https://noasoft.org" target="_blank"><img src="https://placehold.co/1200x90/1A1A1A/FFFFFF?text=Reklam+Alan%C4%B1" style="width:100%;max-width:1200px;"></a>',
    'cursor_style' => getenv('CURSOR_STYLE') ?: 'cursor_1',
    'cursor_primary' => getenv('CURSOR_PRIMARY') ?: '#0f172a',
    'cursor_secondary' => getenv('CURSOR_SECONDARY') ?: '#e11d48',
];
