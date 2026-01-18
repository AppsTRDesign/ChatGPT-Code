<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json; charset=utf-8');
require_login_json();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response(['success' => true, 'user' => $user]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'method_not_allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
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

$updated = $pdo->prepare('SELECT id, name, email, avatar_url FROM users WHERE id = ? LIMIT 1');
$updated->execute([$user['id']]);
$profile = $updated->fetch(PDO::FETCH_ASSOC);

json_response(['success' => true, 'user' => $profile]);
