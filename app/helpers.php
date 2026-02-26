<?php

declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function detect_browser_lang(array $available): string
{
    $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $codes = array_map(static fn($part) => strtolower(substr(trim($part), 0, 2)), explode(',', $header));

    foreach ($codes as $code) {
        if (in_array($code, $available, true)) {
            return $code;
        }
    }

    return DEFAULT_LANG;
}

function current_lang(): string
{
    start_session();

    if (!empty($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS, true)) {
        return $_SESSION['lang'];
    }

    $lang = detect_browser_lang(SUPPORTED_LANGS);
    $_SESSION['lang'] = $lang;

    return $lang;
}

function seo_slug(string $text): string
{
    $map = [
        'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i',
        'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y',
    ];

    $text = strtr($text, $map);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'sayfa';
}

function csrf_token(): string
{
    start_session();

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): bool
{
    start_session();

    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function json_response(bool $ok, string $message, array $data = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function settings(): array
{
    try {
        $stmt = db()->query('SELECT `key_name`, `value` FROM settings');
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $out[$row['key_name']] = $row['value'];
    }

    return $out;
}

function t(string $group, string $key, ?string $lang = null): string
{
    $lang ??= current_lang();

    try {
        $stmt = db()->prepare('SELECT text_value FROM translations WHERE lang_code = :lang AND `group_name` = :grp AND `key_name` = :k LIMIT 1');
        $stmt->execute(['lang' => $lang, 'grp' => $group, 'k' => $key]);
        $value = $stmt->fetchColumn();

        if ($value !== false) {
            return (string) $value;
        }

        $stmt->execute(['lang' => DEFAULT_LANG, 'grp' => $group, 'k' => $key]);
        $fallback = $stmt->fetchColumn();
    } catch (Throwable $e) {
        return $key;
    }

    return $fallback !== false ? (string) $fallback : $key;
}

function admin_auth(): bool
{
    start_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_auth()) {
        header('Location: ' . ADMIN_PATH . '/login.php');
        exit;
    }
}
