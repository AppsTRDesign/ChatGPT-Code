<?php
require_once __DIR__ . '/../../config/config.php';

use App\Activity;
use App\Auth;
use App\Helpers;
use App\Settings;

Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

if (!Settings::onesignalEnabled()) {
    echo json_encode(['campaigns' => []]);
    return;
}

try {
    $user = Auth::user();
    $userId = $user ? (int) $user['id'] : null;
    $playerId = isset($_SESSION['onesignal_player_id']) && is_string($_SESSION['onesignal_player_id'])
        ? trim($_SESSION['onesignal_player_id'])
        : null;
    if ($playerId === '') {
        $playerId = null;
    }

    $sessionKey = Activity::sessionKey();

    $db = Helpers::db();

    $columns = [];
    try {
        $columnStmt = $db->query("SHOW COLUMNS FROM web_push_campaigns");
        $columns = array_map(static fn(array $row) => $row['Field'] ?? null, $columnStmt->fetchAll());
    } catch (\Throwable $schemaException) {
        $columns = [];
    }

    $columns = array_filter(array_map(static fn($value) => is_string($value) ? strtolower($value) : null, $columns));
    $hasAudience = in_array('audience', $columns, true);
    $hasTargetIds = in_array('target_ids', $columns, true);

    $whereParts = ["status = 'sent'"];
    $params = [
        'user_id' => $userId,
        'session_key' => $sessionKey,
    ];

    if ($hasAudience) {
        $targetClauses = ["audience = 'all'"];
        if ($hasTargetIds) {
            $targetClauses[] = "(:user_id IS NOT NULL AND (FIND_IN_SET(CONCAT('user:', :user_id), COALESCE(target_ids, '')) OR FIND_IN_SET(:user_id, COALESCE(target_ids, ''))))";
            $targetClauses[] = "(:player_id IS NOT NULL AND (FIND_IN_SET(CONCAT('player:', :player_id), COALESCE(target_ids, '')) OR FIND_IN_SET(:player_id, COALESCE(target_ids, ''))))";
            $params['player_id'] = $playerId;
        } else {
            $targetClauses[] = '(:user_id IS NOT NULL)';
        }

        $whereParts[] = '(' . implode(' OR ', $targetClauses) . ')';
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereParts);

    $sql = "SELECT id, title, message, target_url, image_path" . ($hasAudience ? ', audience' : '') . "
            FROM web_push_campaigns
            $whereSql
              AND id NOT IN (
                  SELECT campaign_id FROM web_push_events
                  WHERE event_type = 'delivered' AND session_key = :session_key
              )
            ORDER BY sent_at DESC
            LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $campaigns = [];
    $logoUrl = Settings::logoUrl();
    $baseUrl = rtrim(BASE_URL, '/');

    foreach ($stmt->fetchAll() as $row) {
        Activity::logPushEvent((int) $row['id'], 'delivered');

        $image = $row['image_path'] ?: ($logoUrl ? $logoUrl : null);
        if ($image && !str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
            $image = $baseUrl . '/' . ltrim($image, '/');
        }

        $campaigns[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'target_url' => $row['target_url'],
            'image_url' => $image,
        ];
    }

    echo json_encode(['campaigns' => $campaigns]);
} catch (\Throwable $exception) {
    error_log('Push poll failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['campaigns' => [], 'error' => 'Bildirimler alınamadı.']);
}
