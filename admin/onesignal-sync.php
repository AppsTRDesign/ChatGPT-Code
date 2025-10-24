<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\OneSignal;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!Helpers::validateCsrf($csrf)) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması başarısız.']);
    exit;
}

if (!Settings::onesignalEnabled()) {
    echo json_encode(['status' => 'error', 'message' => 'OneSignal ayarları aktif değil.']);
    exit;
}

try {
    $result = OneSignal::syncSubscribers();
    $count = (int) ($result['imported'] ?? 0);
    echo json_encode(['status' => 'success', 'message' => "Aboneler güncellendi. ($count kayıt)", 'imported' => $count]);
} catch (\Throwable $exception) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $exception->getMessage()]);
}
