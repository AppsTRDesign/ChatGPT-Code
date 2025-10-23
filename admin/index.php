<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;

if (!Auth::user()) {
    redirect('/login');
}

Auth::requireRole('admin');
redirect('/admin/dashboard');
