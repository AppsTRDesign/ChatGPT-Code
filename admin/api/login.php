<?php
require_once __DIR__ . '/../../lib/AuthService.php';

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($payload['username'] ?? '');
$password = $payload['password'] ?? '';

$auth = new AuthService();
$auth->ensureDefaultAdmin();

if (!$username || !$password) {
    json_response(['error' => 'Kullanıcı adı ve şifre zorunludur.'], 422);
}

if ($auth->attempt($username, $password)) {
    json_response(['success' => true, 'user' => ['username' => $username]]);
}

json_response(['error' => 'Giriş bilgileri geçersiz.'], 401);
