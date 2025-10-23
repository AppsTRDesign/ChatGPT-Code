<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users');
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    return respond('Geçersiz oturum anahtarı.', false);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    return respond('Geçersiz kullanıcı.', false);
}

if ($id === (int) Auth::user()['id']) {
    return respond('Kendi hesabınızı silemezsiniz.', false);
}

$db = Helpers::db();
$delete = $db->prepare('DELETE FROM users WHERE id = :id');
$delete->execute(['id' => $id]);

return respond('Üye başarıyla silindi.', true);

function respond(string $message, bool $success): never
{
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $acceptsJson = str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if ($isAjax || $acceptsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $message,
        ]);
        exit;
    }

    Helpers::flash('message', $message);
    redirect('/admin/users');
}
