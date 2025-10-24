<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\QrHistory;

Auth::requireRole('client');
Helpers::requireAjax();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek']);
    exit;
}

if (!Helpers::validateCsrf($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması başarısız']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Kayıt bulunamadı']);
    exit;
}

$user = Auth::user();
$deleted = QrHistory::deleteForUser($id, (int) $user['id']);

if (!$deleted) {
    echo json_encode(['status' => 'error', 'message' => 'QR kaydı silinemedi']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'QR kaydı silindi']);
