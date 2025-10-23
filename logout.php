<?php
require_once __DIR__ . '/config/config.php';

use App\Auth;
use App\Helpers;

Auth::logout();
Helpers::flash('message', 'Başarıyla çıkış yapıldı.');
redirect('/');
