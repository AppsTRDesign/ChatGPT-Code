<?php
require_once __DIR__ . '/../../config/config.php';

use App\Activity;
use App\Auth;
use App\Helpers;
use App\NotificationService;
use App\Settings;

Helpers::requireAjax();

header('Content-Type: application/json; charset=utf-8');

Activity::heartbeat();

$user = Auth::user();
$sessionKey = Activity::sessionKey();
$language = isset($_GET['lang']) ? substr((string) $_GET['lang'], 0, 10) : null;
$platform = isset($_GET['platform']) ? substr((string) $_GET['platform'], 0, 20) : null;

$notifications = NotificationService::fetchForSession($sessionKey, $language, $platform, $user ? (int) $user['id'] : null);
$logo = Settings::logoUrl();

$payload = array_map(static function (array $notification) use ($logo) {
    $image = $notification['image_path'] ? rtrim(BASE_URL, '/') . '/' . ltrim($notification['image_path'], '/') : null;
    return [
        'id' => $notification['id'],
        'title' => $notification['title'],
        'message' => $notification['message'],
        'url' => $notification['url'],
        'image' => $image,
        'created_at' => $notification['created_at'],
        'logo' => $logo,
    ];
}, $notifications);

echo json_encode([
    'status' => 'ok',
    'notifications' => $payload,
]);
