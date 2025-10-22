<?php

require_once __DIR__ . '/../../lib/helpers.php';

session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
if (($payload['password'] ?? '') === config()['admin_password']) {
    $_SESSION['admin_authenticated'] = true;
    json_response(['success' => true]);
}

json_response(['error' => 'Geçersiz şifre'], 401);
