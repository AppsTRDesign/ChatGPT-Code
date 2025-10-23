<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/vendor/autoload.php';

use App\Auth;
use App\Helpers;

Auth::logout();
Helpers::flash('message', 'Başarıyla çıkış yapıldı.');
redirect('/');
