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
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
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

function absolute_url(string $path): string
{
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return base_url(ltrim($path, '/'));
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

function excerpt_words(string $text, int $limit = 120): string
{
    $plain = trim(strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($plain === '') {
        return '';
    }
    $words = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($words) <= $limit) {
        return $plain;
    }
    $excerpt = implode(' ', array_slice($words, 0, $limit));
    return $excerpt . '...';
}

function product_url(array $product): string
{
    return '/urun/' . urlencode($product['slug']);
}

function category_url(array $category): string
{
    return '/kategori/' . urlencode($category['slug']);
}

function order_status_label(string $status): string
{
    $labels = [
        'pending' => 'Bekleniyor',
        'approved' => 'Onaylandı',
        'preparing' => 'Hazırlanıyor',
        'shipping' => 'Yola Çıktı',
        'delivered' => 'Teslim Edildi',
    ];
    return $labels[$status] ?? $status;
}

function order_channel_label(string $channel): string
{
    $labels = [
        'whatsapp' => 'WhatsApp',
        'paytr' => 'Kredi Kartı',
        'bank_transfer' => 'Banka Havalesi',
    ];
    return $labels[$channel] ?? $channel;
}

function product_discount_value(array $product): float
{
    $type = $product['discount_type'] ?? '';
    $value = (float) ($product['discount_value'] ?? 0);
    return $value > 0 && in_array($type, ['percent', 'amount'], true) ? $value : 0.0;
}

function product_has_discount(array $product): bool
{
    return product_discount_value($product) > 0;
}

function product_discounted_price(array $product): float
{
    $price = (float) ($product['price'] ?? 0);
    $type = $product['discount_type'] ?? '';
    $value = product_discount_value($product);
    if ($value <= 0) {
        return $price;
    }
    if ($type === 'percent') {
        return max(0.0, $price - ($price * ($value / 100)));
    }
    if ($type === 'amount') {
        return max(0.0, $price - $value);
    }
    return $price;
}

function render_stars(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        $filled = $i <= $rating ? ' filled' : '';
        $output .= '<svg class="star-icon' . $filled . '" viewBox="0 0 24 24" aria-hidden="true">'
            . '<path d="M12 2l2.9 6.4 7 .6-5.2 4.5 1.6 6.8L12 16.9 5.7 20.3 7.3 13.5 2 9l7-.6L12 2z"/></svg>';
    }
    return $output;
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
