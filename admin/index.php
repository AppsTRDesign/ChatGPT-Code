<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;

if (!Auth::user()) {
    redirect('/login');
}

Auth::requireRole('admin');
redirect('/admin/dashboard');
