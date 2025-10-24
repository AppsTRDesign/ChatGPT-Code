<?php
require_once __DIR__ . '/../config/config.php';

use App\Auth;
use App\Helpers;
use App\Notifications;
use App\Settings;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Yalnızca POST isteklerine izin verilir.']);
    exit;
}

Helpers::requireAjax();

$user = Auth::user();
$userId = $user ? (int) $user['id'] : null;

if (!Settings::onesignalEnabled()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'OneSignal entegrasyonu pasif.']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$playerId = trim((string) ($payload['player_id'] ?? ''));
$platform = trim((string) ($payload['platform'] ?? 'web'));
$language = trim((string) ($payload['language'] ?? ''));
$country = trim((string) ($payload['country'] ?? ''));

if ($playerId === '') {
    $previous = trim((string) ($payload['previous'] ?? ''));
    if ($previous !== '') {
        Notifications::removePlayer($previous);
    }
    echo json_encode(['status' => 'ok']);
    exit;
}

$meta = [
    'language' => $language !== '' ? $language : null,
    'country' => $country !== '' ? $country : null,
];

$registered = Notifications::registerPlayer($userId, $playerId, $platform !== '' ? $platform : null, $meta);

if (!$registered) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cihaz kaydedilemedi.']);
    exit;
}

echo json_encode(['status' => 'ok', 'registered' => true]);
