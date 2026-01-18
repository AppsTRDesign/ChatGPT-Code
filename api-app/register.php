<?php
require_once __DIR__ . '/helpers.php';

$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($name === '' || $email === '' || strlen($password) < 6) {
    json_response(['error' => 'invalid_input'], 422);
}

$db = get_pdo();

$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['error' => 'email_exists'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare(
    "INSERT INTO users (name, email, password, status, auth_provider)
     VALUES (?, ?, ?, 'active', 'local')"
);
$stmt->execute([$name, $email, $hash]);

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$db->lastInsertId();

json_response(['success' => true]);
