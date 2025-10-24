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

$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (!$input) {
    $input = $_POST;
}

$csrf = $input['csrf_token'] ?? '';
if (!Helpers::validateCsrf($csrf)) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum doğrulaması başarısız.']);
    exit;
}

if (!Settings::onesignalEnabled()) {
    echo json_encode(['status' => 'error', 'message' => 'OneSignal entegrasyonu pasif durumda.']);
    exit;
}

$title = trim((string) ($input['title'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));
$language = strtolower(trim((string) ($input['language'] ?? 'tr')));
$url = trim((string) ($input['url'] ?? ''));
$imagePath = trim((string) ($input['image_path'] ?? ''));
$targetType = $input['target_type'] === 'players' ? 'players' : 'all';
$playerIds = $input['player_ids'] ?? [];

if ($title === '' || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Başlık ve mesaj alanları zorunludur.']);
    exit;
}

if ($targetType === 'players') {
    if (!is_array($playerIds) || count($playerIds) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'En az bir abone seçmelisiniz.']);
        exit;
    }
    $playerIds = array_values(array_unique(array_map(static function ($value) {
        return substr((string) $value, 0, 80);
    }, $playerIds)));
}

$payload = [
    'headings' => [$language => $title],
    'contents' => [$language => $message],
    'priority' => 10,
];

if ($targetType === 'players' && $playerIds) {
    $payload['include_player_ids'] = $playerIds;
} else {
    $payload['included_segments'] = ['Subscribed Users'];
}

if ($url !== '') {
    $payload['url'] = $url;
}

$logo = Settings::logoUrl();
if ($logo) {
    $payload['chrome_web_icon'] = $logo;
}

if ($imagePath !== '') {
    $absolute = rtrim(BASE_URL, '/') . '/' . ltrim($imagePath, '/');
    $payload['chrome_web_image'] = $absolute;
    $payload['big_picture'] = $absolute;
}

try {
    $response = OneSignal::send($payload);
} catch (\Throwable $exception) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $exception->getMessage()]);
    exit;
}

$onesignalId = $response['id'] ?? null;
$recipientCount = (int) ($response['recipients'] ?? $response['successful'] ?? 0);
$status = $recipientCount > 0 ? 'sent' : 'queued';

$detail = null;
if ($onesignalId) {
    $detail = OneSignal::fetchNotification($onesignalId);
    if (isset($detail['successful'])) {
        $recipientCount = (int) $detail['successful'];
        $status = $recipientCount > 0 ? 'sent' : $status;
    }
}

$stats = [
    'recipients' => $recipientCount,
];
if (is_array($detail)) {
    $stats['successful'] = (int) ($detail['successful'] ?? 0);
    $stats['failed'] = (int) ($detail['failed'] ?? 0);
    $stats['errored'] = (int) ($detail['errored'] ?? 0);
    $stats['converted'] = (int) ($detail['converted'] ?? 0);
}

$db = Helpers::db();

$campaignId = null;
if (Helpers::tableExists('web_push_campaigns')) {
    $stmt = $db->prepare('INSERT INTO web_push_campaigns (onesignal_id, title, message, language, url, image_path, target_type, target_count, status, stats_json, created_by, sent_at, created_at)
        VALUES (:onesignal_id, :title, :message, :language, :url, :image_path, :target_type, :target_count, :status, :stats_json, :created_by, NOW(), NOW())');
    $stmt->execute([
        'onesignal_id' => $onesignalId,
        'title' => $title,
        'message' => $message,
        'language' => $language,
        'url' => $url !== '' ? $url : null,
        'image_path' => $imagePath !== '' ? $imagePath : null,
        'target_type' => $targetType,
        'target_count' => $targetType === 'players' ? count($playerIds) : $recipientCount,
        'status' => $status,
        'stats_json' => json_encode($stats, JSON_UNESCAPED_UNICODE),
        'created_by' => Auth::user()['id'] ?? null,
    ]);
    $campaignId = (int) $db->lastInsertId();
}

if ($campaignId && Helpers::tableExists('web_push_events')) {
    $players = [];
    if ($targetType === 'players' && $playerIds) {
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));
        $query = 'SELECT player_id, country, city, ip, device_type FROM onesignal_subscriptions WHERE player_id IN (' . $placeholders . ')';
        $stmt = $db->prepare($query);
        $stmt->execute($playerIds);
        $players = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    } elseif ($targetType === 'all') {
        $players = $db->query('SELECT player_id, country, city, ip, device_type FROM onesignal_subscriptions')->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    if ($players) {
        $insert = $db->prepare('INSERT INTO web_push_events (campaign_id, event_type, player_id, country, city, ip, platform, count, created_at)
            VALUES (:campaign_id, :event_type, :player_id, :country, :city, :ip, :platform, :count, NOW())');
        foreach ($players as $player) {
            $insert->execute([
                'campaign_id' => $campaignId,
                'event_type' => 'queued',
                'player_id' => $player['player_id'] ?? null,
                'country' => $player['country'] ?? null,
                'city' => $player['city'] ?? null,
                'ip' => $player['ip'] ?? null,
                'platform' => $player['device_type'] ?? null,
                'count' => 1,
            ]);
        }
    }
}

if ($campaignId && Helpers::tableExists('web_push_events')) {
    $summaryMap = [
        'sent' => 'successful',
        'delivered' => 'delivered',
        'opened' => 'opened',
        'clicked' => 'clicked',
    ];
    $summaryInsert = $db->prepare('INSERT INTO web_push_events (campaign_id, event_type, player_id, country, city, ip, platform, count, created_at)
        VALUES (:campaign_id, :event_type, NULL, NULL, NULL, NULL, :platform, :count, NOW())');
    foreach ($summaryMap as $eventType => $key) {
        $value = isset($stats[$key]) ? (int) $stats[$key] : 0;
        if ($eventType === 'sent' && !$value) {
            $value = (int) ($stats['recipients'] ?? 0);
        }
        if ($value > 0) {
            $summaryInsert->execute([
                'campaign_id' => $campaignId,
                'event_type' => $eventType,
                'platform' => 'Toplam',
                'count' => $value,
            ]);
        }
    }
}

if ($campaignId) {
    try {
        OneSignal::refreshCampaignStats($campaignId);
    } catch (\Throwable $exception) {
        error_log('Push kampanya istatistikleri güncellenemedi: ' . $exception->getMessage());
    }
}

echo json_encode([
    'status' => 'success',
    'message' => 'Bildirim OneSignal üzerinden sıraya alındı.',
    'onesignal_id' => $onesignalId,
    'recipients' => $recipientCount,
]);
