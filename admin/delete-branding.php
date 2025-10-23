<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;
use App\Settings;

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/admin/settings');
}

$type = $_POST['type'] ?? 'logo';
$key = $type === 'favicon' ? 'site_favicon' : 'site_logo';
$current = Settings::get($key);

if ($current) {
    $filePath = __DIR__ . '/../' . ltrim((string) $current, '/');
    if (is_file($filePath)) {
        @unlink($filePath);
    }
    Settings::remove($key);
}

Helpers::flash('message', ucfirst($type) . ' kaldırıldı.');
redirect('/admin/settings');
