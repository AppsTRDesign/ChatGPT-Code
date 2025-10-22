<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;

if (!Auth::user()) {
    redirect('/login.php');
}

Auth::requireRole('admin');
redirect('/admin/dashboard.php');
