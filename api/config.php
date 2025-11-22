<?php
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: 'autosurf',
    'db_user' => getenv('DB_USER') ?: 'autosurf_user',
    'db_pass' => getenv('DB_PASS') ?: 'change_me',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change_this_secret',
    'initial_points' => 300,
    'contact_email' => getenv('CONTACT_EMAIL') ?: 'info@noasoft.org',
    // Kullanıcı başına sınırlar (0 = sınırsız)
    'max_daily_site_visits' => 2,
    'max_daily_reward' => 1200,
    'max_weekly_reward' => 5000,
    'max_monthly_reward' => 12000,
    'google_task_points' => 50,
    'youtube_task_points' => 50,
    'google_page_points' => 10,
    'youtube_page_points' => 10,
];
