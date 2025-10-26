<?php
declare(strict_types=1);

const BASE_URL = 'https://fileupload.noasoft.org';

$database = [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'fileupload',
    'user' => 'fileupload_user',
    'pass' => 'change_me',
    'charset' => 'utf8mb4',
];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $database['host'], $database['port'], $database['name'], $database['charset']);
    $pdo = new PDO($dsn, $database['user'], $database['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

require_once __DIR__ . '/functions.php';

ensureDefaultPackages($pdo);
ensureDatabaseSchema($pdo);
ensureDefaultSettings($pdo);
ensure_admin_exists($pdo);

