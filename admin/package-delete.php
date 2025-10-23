<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/packages');
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    Helpers::flash('message', 'Geçersiz oturum anahtarı.');
    redirect('/admin/packages');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id) {
    PackageManager::delete($id);
    Helpers::flash('message', 'Paket silindi.');
}

redirect('/admin/packages');
