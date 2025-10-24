<?php
require_once __DIR__ . '/../../config/config.php';

use App\Activity;
use App\Auth;
use App\Helpers;
use App\Settings;

Helpers::requireAjax();

$user = Auth::user();
$userId = $user ? (int) $user['id'] : null;
$sessionKey = Activity::sessionKey();

$db = Helpers::db();

$sql = "SELECT id, title, message, target_url, image_path, audience FROM web_push_campaigns
        WHERE status = 'sent'
          AND (audience = 'all' OR (:user_id IS NOT NULL AND target_ids IS NOT NULL AND FIND_IN_SET(:user_id, target_ids)))
          AND id NOT IN (
              SELECT campaign_id FROM web_push_events
              WHERE event_type = 'delivered' AND session_key = :session_key
          )
        ORDER BY sent_at DESC
        LIMIT 10";

$stmt = $db->prepare($sql);
$stmt->execute([
    'user_id' => $userId,
    'session_key' => $sessionKey,
]);

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

header('Content-Type: application/json; charset=utf-8');

echo json_encode(['campaigns' => $campaigns]);
