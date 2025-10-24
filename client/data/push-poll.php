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

if (!Helpers::tableExists('web_push_campaigns')) {
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

    $columns = Helpers::tableColumns('web_push_campaigns');
    $columnSet = array_flip($columns);

    $hasAudience = isset($columnSet['audience']);
    $hasTargetIds = isset($columnSet['target_ids']);
    $hasStatus = isset($columnSet['status']);
    $hasTargetUrl = isset($columnSet['target_url']);
    $hasImagePath = isset($columnSet['image_path']);
    $hasSentAt = isset($columnSet['sent_at']);

    $selectParts = ['id', 'title', 'message'];
    $selectParts[] = $hasTargetUrl ? 'target_url' : 'NULL AS target_url';
    $selectParts[] = $hasImagePath ? 'image_path' : 'NULL AS image_path';
    if ($hasAudience) {
        $selectParts[] = 'audience';
    }

    $whereParts = [];
    $params = [];

    if ($hasStatus) {
        $whereParts[] = "status = 'sent'";
    }

    if ($hasAudience) {
        $targetClauses = ["audience = 'all'"];
        $params['user_id'] = $userId;

        if ($hasTargetIds) {
            $targetClauses[] = "(:user_id IS NOT NULL AND (FIND_IN_SET(CONCAT('user:', :user_id), COALESCE(target_ids, '')) OR FIND_IN_SET(:user_id, COALESCE(target_ids, ''))))";
            $params['player_id'] = $playerId;
            $targetClauses[] = "(:player_id IS NOT NULL AND (FIND_IN_SET(CONCAT('player:', :player_id), COALESCE(target_ids, '')) OR FIND_IN_SET(:player_id, COALESCE(target_ids, ''))))";
        }

        $whereParts[] = '(' . implode(' OR ', $targetClauses) . ')';
    }

    $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

    $exclusionSql = '';
    $hasEventsTable = Helpers::tableExists('web_push_events');
    if ($hasEventsTable) {
        $exclusionSql = <<<SQL
 AND id NOT IN (
                  SELECT campaign_id FROM web_push_events
                  WHERE event_type = 'delivered' AND session_key = :session_key
              )
SQL;
        $params['session_key'] = $sessionKey;
    }

    $orderColumn = $hasSentAt ? 'sent_at' : 'id';

    $sql = sprintf(
        'SELECT %s FROM web_push_campaigns %s%s ORDER BY %s DESC LIMIT 10',
        implode(', ', $selectParts),
        $whereSql,
        $exclusionSql,
        $orderColumn
    );

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();

    $campaigns = [];
    $logoUrl = Settings::logoUrl();
    $baseUrl = rtrim(BASE_URL, '/');

    foreach ($stmt->fetchAll() as $row) {
        if ($hasEventsTable) {
            Activity::logPushEvent((int) $row['id'], 'delivered');
        }

        $imagePath = $row['image_path'] ?? null;
        $image = $imagePath ?: ($logoUrl ? $logoUrl : null);
        if ($image && !str_starts_with($image, 'http://') && !str_starts_with($image, 'https://')) {
            $image = $baseUrl . '/' . ltrim($image, '/');
        }

        $campaigns[] = [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'target_url' => $row['target_url'] ?? null,
            'image_url' => $image,
        ];
    }

    echo json_encode(['campaigns' => $campaigns]);
} catch (\Throwable $exception) {
    error_log('Push poll failed: ' . $exception->getMessage());
    echo json_encode(['campaigns' => []]);
}
