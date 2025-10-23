<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\PackageManager;

Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/packages');
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    return respond('Geçersiz oturum anahtarı.', false);
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    return respond('Paket bulunamadı.', false);
}

$success = PackageManager::toggle($id);
$message = $success ? 'Paket durumu güncellendi.' : 'Paket durumu güncellenemedi.';

respond($message, $success);
return;

function respond(string $message, bool $success): void
{
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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
    redirect('/admin/packages');
}
