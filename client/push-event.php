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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    $data = $_POST;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!Helpers::validateCsrf($token)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulanamadı.']);
    exit;
}

$campaignId = isset($data['campaign_id']) ? (int) $data['campaign_id'] : 0;
$event = isset($data['event']) ? (string) $data['event'] : '';

if ($campaignId <= 0 || $event === '') {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Eksik veya hatalı veri gönderildi.']);
    exit;
}

$overrides = [
    'referer' => isset($data['referer']) ? filter_var((string) $data['referer'], FILTER_SANITIZE_URL) : null,
    'search_engine' => $data['search_engine'] ?? null,
    'search_term' => $data['search_term'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
];

Activity::logPushEvent($campaignId, $event, $overrides);

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['status' => 'ok']);
