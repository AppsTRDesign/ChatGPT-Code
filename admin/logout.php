<?php
require_once __DIR__ . '/../lib/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest') {
    json_response(['error' => 'Geçersiz istek yöntemi.'], 405);
}

$auth = new AuthService();
$auth->logout();

json_response(['success' => true]);
