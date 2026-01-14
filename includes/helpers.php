<?php

require_once __DIR__ . '/db.php';

function settings(string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
    $stmt->execute(['key' => $key]);
    $value = $stmt->fetchColumn();
    $cache[$key] = $value !== false ? (string) $value : $default;

    return $cache[$key];
}

function update_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value');
    $stmt->execute(['key' => $key, 'value' => $value]);
}

function currency(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' ₺';
}

function base_url(string $path = ''): string
{
    $base = rtrim(settings('base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function permalink(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $tr = [
        'Ç' => 'C', 'Ş' => 'S', 'Ğ' => 'G', 'Ü' => 'U', 'İ' => 'I', 'Ö' => 'O',
        'ç' => 'c', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ı' => 'i', 'ö' => 'o',
    ];
    $text = strtr($text, $tr);

    if (class_exists('Transliterator')) {
        $tr = Transliterator::create('Any-Latin; Latin-ASCII');
        if ($tr) {
            $text = $tr->transliterate($text);
        }
    } else {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);

    return trim($text, '-');
}

function product_url(array $product): string
{
    return '/urun/' . urlencode($product['slug']);
}

function category_url(array $category): string
{
    return '/kategori/' . urlencode($category['slug']);
}

function is_admin(): bool
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız.']);
        exit;
    }
}
