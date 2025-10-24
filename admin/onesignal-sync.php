<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Notifications;
use App\Settings;

Auth::requireRole('admin');
Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Yalnızca POST isteği kabul edilir.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$token = $payload['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!Helpers::validateCsrf($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Oturum doğrulaması başarısız.']);
    exit;
}

if (!Settings::onesignalEnabled()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'OneSignal entegrasyonu pasif.']);
    exit;
}

try {
    $result = Notifications::syncFromOneSignal();
} catch (\Throwable $exception) {
    error_log('OneSignal sync failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'OneSignal aboneleri alınamadı.']);
    exit;
}

if (!$result['success']) {
    http_response_code(500);
    echo json_encode($result);
    exit;
}

echo json_encode($result);
