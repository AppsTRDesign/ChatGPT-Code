<?php
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');
require_login_json();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

if (empty($_FILES['file']['tmp_name'])) {
    json_response(['error' => 'file_missing'], 422);
}

$tmp = $_FILES['file']['tmp_name'];
if (!@getimagesize($tmp)) {
    json_response(['error' => 'invalid_image'], 422);
}

try {
    $root = dirname(__DIR__);
    $dir = $root . '/uploads/user/local/avatars/' . $user['id'] . '/';
    ensure_dir($dir);

    $nameBase = 'avatar-' . substr(sha1($user['id'] . microtime(true) . rand()), 0, 14);
    $mainPath = $dir . $nameBase . '.webp';

    $okMain = save_webp_resized($tmp, $mainPath, (int)MAIN_MAX_WIDTH, (int)WEBP_QUALITY);

    if (!$okMain) {
        @unlink($mainPath);
        json_response(['error' => 'save_failed'], 500);
    }

    $url = rtrim(BASE_URL, '/') . "/uploads/user/local/avatars/{$user['id']}/" . basename($mainPath);

    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ? WHERE id = ?');
    $stmt->execute([$url, $user['id']]);

    json_response(['status' => 'ok', 'url' => $url]);
} catch (Throwable $e) {
    json_response(['error' => 'server_error'], 500);
}
