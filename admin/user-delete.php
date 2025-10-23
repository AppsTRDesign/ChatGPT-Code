<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users');
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    Helpers::flash('message', 'Geçersiz oturum anahtarı.');
    redirect('/admin/users');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    Helpers::flash('message', 'Geçersiz kullanıcı.');
    redirect('/admin/users');
}

if ($id === (int) Auth::user()['id']) {
    Helpers::flash('message', 'Kendi hesabınızı silemezsiniz.');
    redirect('/admin/users');
}

$db = Helpers::db();
$delete = $db->prepare('DELETE FROM users WHERE id = :id');
$delete->execute(['id' => $id]);

Helpers::flash('message', 'Üye başarıyla silindi.');
redirect('/admin/users');
