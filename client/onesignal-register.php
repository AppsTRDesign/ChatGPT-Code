<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Notifications;
use App\Settings;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Yalnızca POST isteklerine izin verilir.']);
    exit;
}

$user = Auth::user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Oturum bulunamadı.']);
    exit;
}

if (!Settings::onesignalEnabled()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'OneSignal entegrasyonu pasif.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$playerId = trim((string) ($payload['player_id'] ?? ''));
$platform = trim((string) ($payload['platform'] ?? 'web'));

if ($playerId === '') {
    $previous = trim((string) ($payload['previous'] ?? ''));
    if ($previous !== '') {
        Notifications::removePlayer($previous);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

Notifications::registerPlayer((int) $user['id'], $playerId, $platform ?: null);

echo json_encode(['status' => 'ok']);
