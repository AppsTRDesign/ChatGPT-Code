<?php

declare(strict_types=1);

session_start();

const APP_NAME = 'NoaSoft QR Menu';
const BASE_URL = 'https://qrmenu.noasoft.org';

const DB_HOST = 'localhost';
const DB_NAME = 'qrmenu';
const DB_USER = 'qrmenu_user';
const DB_PASS = 'change_me';
const DB_CHARSET = 'utf8mb4';

function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
}

function asset(string $path): string
{
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
