<?php
require_once __DIR__ . '/../../config/config.php';

use App\Activity;
use App\Auth;
use App\Helpers;
use App\NotificationService;

Helpers::requireAjax();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Yalnızca POST desteklenir.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = isset($input['id']) ? (int) $input['id'] : 0;
$action = $input['action'] ?? '';
$language = isset($input['language']) ? substr((string) $input['language'], 0, 10) : null;
$platform = isset($input['platform']) ? substr((string) $input['platform'], 0, 20) : null;

if ($notificationId <= 0 || !in_array($action, [NotificationService::ACTION_CLICKED, NotificationService::ACTION_DISMISSED], true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit;
}

Activity::heartbeat();
$sessionKey = Activity::sessionKey();
$user = Auth::user();

NotificationService::recordEvent(
    $notificationId,
    $sessionKey,
    $action,
    [
        'user_id' => $user ? (int) $user['id'] : null,
        'language' => $language,
        'platform' => $platform,
    ]
);

echo json_encode(['status' => 'ok']);
