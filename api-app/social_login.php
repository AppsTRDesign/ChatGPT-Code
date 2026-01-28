<?php
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$provider = strtolower(trim((string)($input['provider'] ?? '')));
$allowedProviders = ['google', 'apple', 'facebook', 'twitter'];
if (!in_array($provider, $allowedProviders, true)) {
    json_response(['error' => 'invalid_provider'], 422);
}

$email = trim((string)($input['email'] ?? ''));
$name = trim((string)($input['name'] ?? ''));
$avatarUrl = trim((string)($input['avatar_url'] ?? ''));

if ($email === '') {
    json_response(['error' => 'missing_email'], 422);
}

if ($name === '') {
    $name = strtok($email, '@') ?: 'Kullanıcı';
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT id, status, auth_provider, avatar_url, profile_photo FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if (($user['status'] ?? '') !== 'active') {
        json_response(['error' => 'inactive_user'], 403);
    }

    $updates = [];
    $params = [];
    if (empty($user['auth_provider'])) {
        $updates[] = 'auth_provider = ?';
        $params[] = $provider;
    }
    if ($avatarUrl !== '' && empty($user['profile_photo']) && empty($user['avatar_url'])) {
        $updates[] = 'avatar_url = ?';
        $params[] = $avatarUrl;
        $updates[] = 'profile_photo = ?';
        $params[] = $avatarUrl;
    }
    if ($updates) {
        $params[] = $user['id'];
        $updateSql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $pdo->prepare($updateSql)->execute($params);
    }

    $userId = (int)$user['id'];
} else {
    $randomPassword = bin2hex(random_bytes(16));
    $hash = password_hash($randomPassword, PASSWORD_DEFAULT);
    $avatarForInsert = $avatarUrl !== '' ? $avatarUrl : null;
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password, status, auth_provider, avatar_url, profile_photo)\n"
        . "VALUES (?, ?, ?, 'active', ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $hash, $provider, $avatarForInsert, $avatarForInsert]);
    $userId = (int)$pdo->lastInsertId();
}

session_regenerate_id(true);
$_SESSION['user_id'] = $userId;

$profile = get_user_by_id($userId);

json_response([
    'success' => true,
    'provider' => $provider,
    'user' => $profile
]);
