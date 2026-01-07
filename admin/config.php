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

function admin_remove_file(?string $url): void
{
    $url = trim((string) $url);
    if ($url === '') {
        return;
    }
    if (strpos($url, '//') === 0) {
        $url = 'https:' . $url;
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['path'])) {
        return;
    }
    $baseHost = parse_url(BASE_URL, PHP_URL_HOST);
    if (!empty($parts['host']) && $baseHost && $parts['host'] !== $baseHost) {
        return;
    }
    $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($root === '') {
        return;
    }
    $path = rtrim($root, '/') . $parts['path'];
    if (is_file($path)) {
        @unlink($path);
    }
}

function admin_remove_files(array $urls): void
{
    foreach ($urls as $url) {
        admin_remove_file($url);
    }
}
