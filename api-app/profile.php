<?php
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId <= 0) {
        json_response(['error' => 'missing_user'], 422);
    }
    $profile = get_user_by_id($userId);
    if (!$profile) {
        json_response(['error' => 'not_found'], 404);
    }
    json_response(['success' => true, 'user' => $profile]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$user = require_user_payload($data);
$name = trim($data['name'] ?? $user['name'] ?? '');
$email = trim($data['email'] ?? $user['email'] ?? '');
$password = $data['password'] ?? null;

if ($name === '' || $email === '') {
    json_response(['error' => 'invalid_input'], 422);
}

$pdo = get_pdo();

$exists = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
$exists->execute([$email, $user['id']]);
if ($exists->fetch()) {
    json_response(['error' => 'email_exists'], 409);
}

if ($password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
    $stmt->execute([$name, $email, $hash, $user['id']]);
} else {
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $stmt->execute([$name, $email, $user['id']]);
}

$updated = $pdo->prepare('SELECT id, name, email, role, avatar_url, profile_photo FROM users WHERE id = ? LIMIT 1');
$updated->execute([$user['id']]);
$profile = $updated->fetch(PDO::FETCH_ASSOC);

json_response(['success' => true, 'user' => $profile]);
