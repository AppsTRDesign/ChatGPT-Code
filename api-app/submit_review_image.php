<?php
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'missing_user']);
    exit;
}

$placeId = (int)($_POST['place_id'] ?? 0);
if ($placeId <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_place_id']);
    exit;
}

if (empty($_FILES['file']['tmp_name'])) {
    http_response_code(422);
    echo json_encode(['error' => 'file_missing']);
    exit;
}

$tmp = $_FILES['file']['tmp_name'];
if (!@getimagesize($tmp)) {
    http_response_code(422);
    echo json_encode(['error' => 'invalid_image']);
    exit;
}

try {
    $root = dirname(__DIR__);
    $rev = $root . '/uploads/user/local/reviews-photo/';
    $dir = rtrim($rev, '/') . '/' . $placeId . '/';
    ensure_dir($dir);

    $nameBase = 'review-' . substr(sha1($userId . microtime(true) . rand()), 0, 14);
    $mainPath = $dir . $nameBase . '.webp';
    $thumbPath = $dir . $nameBase . '-thumb.webp';

    $okMain = save_webp_resized($tmp, $mainPath, (int)MAIN_MAX_WIDTH, (int)WEBP_QUALITY);
    $okThumb = save_webp_resized($tmp, $thumbPath, (int)THUMB_MAX_WIDTH, (int)WEBP_QUALITY);

    if (!$okMain || !$okThumb) {
        @unlink($mainPath);
        @unlink($thumbPath);
        http_response_code(500);
        echo json_encode(['error' => 'save_failed']);
        exit;
    }

    $url = rtrim(BASE_URL, '/') . "/uploads/user/local/reviews-photo/{$placeId}/" . basename($mainPath);

    echo json_encode([
        'status' => 'ok',
        'url' => $url
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
}
