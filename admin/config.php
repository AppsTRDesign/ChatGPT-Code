<?php
// Admin config bootstrap
require_once __DIR__ . '/../frontend/config.php';
require_once __DIR__ . '/../frontend/db.php';
require_once __DIR__ . '/../frontend/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_db(): PDO
{
    return get_pdo();
}

function admin_json($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
