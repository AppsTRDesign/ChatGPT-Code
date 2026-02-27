<?php

declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function available_languages(): array
{
    static $cache = null;

    if (is_array($cache)) {
        return $cache;
    }

    try {
        $rows = db()->query('SELECT code, name FROM languages WHERE is_active = 1 ORDER BY sort_order, code')->fetchAll();
        if ($rows) {
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['code']] = $row['name'];
            }

            return $cache;
        }
    } catch (Throwable $e) {
    }

    $cache = [];
    foreach (SUPPORTED_LANGS as $code) {
        $cache[$code] = strtoupper($code);
    }

    return $cache;
}

function supported_langs(): array
{
    return array_keys(available_languages());
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
    $available = supported_langs();

    if (!empty($_GET['lang']) && in_array($_GET['lang'], $available, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $available, true)) {
        return $_SESSION['lang'];
    }

    $lang = detect_browser_lang($available);
    $_SESSION['lang'] = $lang;

    return $lang;
}

function yandex_locale(?string $lang = null): string
{
    $code = strtolower((string)($lang ?? current_lang()));

    return match ($code) {
        'tr' => 'tr_TR',
        'de' => 'de_DE',
        'fr' => 'fr_FR',
        default => 'en_US',
    };
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

    return $text !== '' ? $text : 'page';
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


function fallback_translations(): array
{
    return [
        'en' => [
            'front' => [
                'top_message' => 'Integrated Global Cargo Operations',
                'track' => 'Track',
                'pricing' => 'Pricing',
                'contact' => 'Contact',
                'tracking_number' => 'Tracking Number',
                'captcha' => 'Verification',
                'track_desc' => 'Enter your tracking number to view shipment details, status timeline and live location.',
                'tracking_waiting' => 'No query yet. Enter a tracking number to display shipment details.',
                'contact_desc' => 'Contact our corporate cargo desk for route planning and account support.',
                'contact_card_title' => 'Corporate Contact Office',
                'contact_card_desc' => 'Fast response for customs, route and contract operations.',
                'address' => 'Address',
                'phone' => 'Phone',
                'email' => 'Email',
                'status_label' => 'Status',
                'current_location' => 'Current Location',
                'route' => 'Route',
                'sender' => 'Sender',
                'receiver' => 'Receiver',
                'timeline' => 'Timeline',
                'description' => 'Description',
                'no_event' => 'No event yet.',
                'from' => 'From',
                'to' => 'To',
                'active_shipments' => 'Active Shipments',
                'active_shipments_desc' => 'Track live vessel and line-haul locations across global cargo routes.',
            ],
        ],
        'tr' => [
            'front' => [
                'top_message' => 'Entegre Global Kargo Operasyonları',
                'track' => 'Kargo Takip',
                'pricing' => 'Fiyatlama',
                'contact' => 'İletişim',
                'tracking_number' => 'Takip Numarası',
                'captcha' => 'Doğrulama',
                'track_desc' => 'Takip numarası ile gönderi detayını, durum zaman çizelgesini ve canlı konumu görüntüleyin.',
                'tracking_waiting' => 'Henüz sorgu yapılmadı. Takip numarası girerek detayları görüntüleyin.',
                'contact_desc' => 'Rota planlama ve kurumsal hesap desteği için kargo masamıza ulaşın.',
                'contact_card_title' => 'Kurumsal İletişim Ofisi',
                'contact_card_desc' => 'Gümrük, rota ve sözleşme operasyonlarında hızlı geri dönüş.',
                'address' => 'Adres',
                'phone' => 'Telefon',
                'email' => 'E-posta',
                'status_label' => 'Durum',
                'current_location' => 'Anlık Konum',
                'route' => 'Rota',
                'sender' => 'Gönderici',
                'receiver' => 'Alıcı',
                'timeline' => 'Zaman Çizelgesi',
                'description' => 'Açıklama',
                'no_event' => 'Henüz event yok.',
                'from' => 'Çıkış',
                'to' => 'Varış',
                'active_shipments' => 'Aktif Gönderiler',
                'active_shipments_desc' => 'Küresel kargo rotalarında gemi ve hat taşıma konumlarını canlı izleyin.',
            ],
        ],
    ];
}

function t(string $group, string $key, ?string $lang = null): string
{
    $lang ??= current_lang();
    $fallback = false;

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
    }

    if ($fallback !== false) {
        return (string) $fallback;
    }

    $map = fallback_translations();
    if (isset($map[$lang][$group][$key])) {
        return $map[$lang][$group][$key];
    }
    if (isset($map[DEFAULT_LANG][$group][$key])) {
        return $map[DEFAULT_LANG][$group][$key];
    }

    return $key;
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
