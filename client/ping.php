<?php
require_once __DIR__ . '/../config/config.php';

use App\Activity;
use App\Helpers;

Helpers::requireAjax();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!Helpers::validateCsrf($token)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulanamadı.']);
    exit;
}

Activity::heartbeat('client');

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['status' => 'ok']);
