<?php

namespace App\Services;

use Core\Database;
use PDO;

class SettingsService
{
    private const DEFAULT_ORDER_SOUND = 'assets/vendor/sounds/order.mp3';
    private const DEFAULT_WAITER_SOUND = 'assets/vendor/sounds/notification.mp3';
    private PDO $db;
    private int $restaurantId;

    public function __construct(int $restaurantId = 1)
    {
        $this->db = Database::connection();
        $this->restaurantId = $restaurantId;
    }

    public function all(): array
    {
        $restaurant = $this->fetchRestaurant();
        $branding = [
            'logo' => $this->mediaUrl($restaurant['logo'] ?? null),
            'favicon' => $this->mediaUrl($restaurant['favicon'] ?? null),
            'qr_logo' => $this->mediaUrl($restaurant['qr_logo'] ?? null),
        ];

        $restaurant['logo'] = $branding['logo'];
        $restaurant['favicon'] = $branding['favicon'];
        $restaurant['qr_logo'] = $branding['qr_logo'];

        $qr = $this->getSection('qr');
        if (!empty($branding['qr_logo'])) {
            $qr['logo_url'] = $branding['qr_logo'];
            $qr['logo'] = $branding['qr_logo'];
        } elseif (!empty($qr['logo']) && empty($qr['logo_url'])) {
            $qr['logo_url'] = $this->mediaUrl($qr['logo']);
        }

        return [
            'restaurant' => $restaurant,
            'qr' => $qr,
            'branding' => $branding,
            'currencies' => $this->currencies(),
            'languages' => $this->languages(),
            'notifications' => $this->notifications(),
            'daily_menu' => $this->dailyMenu(),
            'timezones' => $this->timezones(),
        ];
    }

    public function update(array $data): array
    {
        if (!empty($data['restaurant'])) {
            $this->updateRestaurant($data['restaurant']);
        }

        if (!empty($data['qr'])) {
            $this->saveSection('qr', $data['qr']);
        }

        if (!empty($data['branding'])) {
            $this->updateBranding($data['branding']);
        }

        if (!empty($data['notifications'])) {
            $this->updateNotifications($data['notifications']);
        }

        if (array_key_exists('daily_menu', $data)) {
            $this->saveDailyMenu($data['daily_menu'] ?? []);
        }

        return $this->all();
    }

    public function dailyMenu(): array
    {
        $section = $this->getSection('daily_menu');
        $items = $section['items'] ?? [];

        $normalized = array_map(function ($item) {
            $id = (string)($item['id'] ?? '');
            $productId = (int)($item['product_id'] ?? 0);
            $headline = trim((string)($item['headline'] ?? ''));
            $tagline = trim((string)($item['tagline'] ?? ''));
            $badge = trim((string)($item['badge'] ?? ''));
            $position = (int)($item['position'] ?? 0);

            if ($id === '') {
                $id = $this->generateDailyMenuId();
            }

            return [
                'id' => $id,
                'product_id' => $productId,
                'headline' => $headline,
                'tagline' => $tagline,
                'badge' => $badge,
                'position' => $position,
            ];
        }, $items);

        usort($normalized, static fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return array_values($normalized);
    }

    public function saveDailyMenuItem(array $payload): array
    {
        $items = $this->dailyMenu();
        $id = (string)($payload['id'] ?? '');
        $productId = (int)($payload['product_id'] ?? 0);

        if ($productId <= 0) {
            throw new \InvalidArgumentException('Günün menüsü için ürün seçilmelidir.');
        }

        $headline = trim((string)($payload['headline'] ?? ''));
        $tagline = trim((string)($payload['tagline'] ?? ''));
        $badge = trim((string)($payload['badge'] ?? ''));

        if ($headline === '') {
            $headline = trim((string)($payload['name'] ?? ''));
        }

        if ($id === '') {
            $id = $this->generateDailyMenuId();
            $position = count($items) + 1;
            $items[] = [
                'id' => $id,
                'product_id' => $productId,
                'headline' => $headline,
                'tagline' => $tagline,
                'badge' => $badge,
                'position' => $position,
            ];
        } else {
            $updated = false;
            foreach ($items as &$item) {
                if ($item['id'] === $id) {
                    $item['product_id'] = $productId;
                    $item['headline'] = $headline;
                    $item['tagline'] = $tagline;
                    $item['badge'] = $badge;
                    $updated = true;
                    break;
                }
            }
            unset($item);

            if (!$updated) {
                $items[] = [
                    'id' => $id,
                    'product_id' => $productId,
                    'headline' => $headline,
                    'tagline' => $tagline,
                    'badge' => $badge,
                    'position' => count($items) + 1,
                ];
            }
        }

        $this->saveDailyMenu($items);

        return $this->dailyMenu();
    }

    public function deleteDailyMenuItem(string $id): array
    {
        $items = array_filter($this->dailyMenu(), static fn($item) => $item['id'] !== $id);
        $this->saveDailyMenu(array_values($items));

        return $this->dailyMenu();
    }

    public function reorderDailyMenu(array $order): array
    {
        $items = $this->dailyMenu();
        $positions = [];
        $index = 1;
        foreach ($order as $itemId) {
            $positions[(string)$itemId] = $index++;
        }

        foreach ($items as &$item) {
            $item['position'] = $positions[$item['id']] ?? $item['position'];
        }
        unset($item);

        usort($items, static fn($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        $this->saveDailyMenu($items);

        return $this->dailyMenu();
    }

    public function currencies(): array
    {
        $query = $this->db->prepare('SELECT id, code, symbol, name, is_default FROM restaurant_currencies WHERE restaurant_id = ? ORDER BY is_default DESC, name ASC');
        $query->execute([$this->restaurantId]);
        $currencies = $query->fetchAll() ?: [];

        return array_map(function ($currency) {
            $currency['code'] = strtoupper($currency['code']);
            $currency['symbol'] = $currency['symbol'] ?? '';
            $currency['name'] = $currency['name'] ?? $currency['code'];
            $currency['is_default'] = (int)($currency['is_default'] ?? 0);
            return $currency;
        }, $currencies);
    }

    public function addCurrency(array $currency): array
    {
        if (!empty($currency['is_default'])) {
            $this->db->prepare('UPDATE restaurant_currencies SET is_default = 0 WHERE restaurant_id = ?')->execute([$this->restaurantId]);
        }

        $statement = $this->db->prepare('INSERT INTO restaurant_currencies (restaurant_id, code, symbol, name, is_default) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE symbol = VALUES(symbol), name = VALUES(name), is_default = VALUES(is_default)');
        $statement->execute([
            $this->restaurantId,
            strtoupper($currency['code']),
            $currency['symbol'] ?? '₺',
            $currency['name'] ?? strtoupper($currency['code']),
            !empty($currency['is_default']) ? 1 : 0,
        ]);

        if (!empty($currency['is_default'])) {
            $this->setDefaultCurrency($currency['code']);
        }

        return $this->currencies();
    }

    public function deleteCurrency(int $id): array
    {
        $statement = $this->db->prepare('DELETE FROM restaurant_currencies WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);

        return $this->currencies();
    }

    public function setDefaultCurrency(string $code): array
    {
        $this->db->prepare('UPDATE restaurant_currencies SET is_default = 0 WHERE restaurant_id = ?')->execute([$this->restaurantId]);
        $statement = $this->db->prepare('UPDATE restaurant_currencies SET is_default = 1 WHERE restaurant_id = ? AND code = ?');
        $statement->execute([$this->restaurantId, strtoupper($code)]);

        $this->db->prepare('UPDATE restaurants SET currency = ?, updated_at = NOW() WHERE id = ?')->execute([
            strtoupper($code),
            $this->restaurantId,
        ]);

        return $this->currencies();
    }

    public function languages(): array
    {
        $statement = $this->db->prepare('SELECT code, label, CASE WHEN code = (SELECT language FROM restaurants WHERE id = ?) THEN 1 ELSE 0 END AS is_default FROM restaurant_languages WHERE restaurant_id = ? ORDER BY label');
        $statement->execute([$this->restaurantId, $this->restaurantId]);
        $languages = $statement->fetchAll() ?: [];

        return array_map(static function ($language) {
            $language['code'] = strtolower($language['code']);
            $language['is_default'] = (int)($language['is_default'] ?? 0);
            return $language;
        }, $languages);
    }

    public function saveLanguageMeta(string $code, string $label): array
    {
        $statement = $this->db->prepare('INSERT INTO restaurant_languages (restaurant_id, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)');
        $statement->execute([$this->restaurantId, strtolower($code), $label]);

        return $this->languages();
    }

    public function deleteLanguage(string $code): array
    {
        $code = strtolower($code);
        $statement = $this->db->prepare('DELETE FROM restaurant_languages WHERE restaurant_id = ? AND code = ?');
        $statement->execute([$this->restaurantId, $code]);

        if ($this->currentLanguage() === $code) {
            $fallback = $this->db->prepare('SELECT code FROM restaurant_languages WHERE restaurant_id = ? ORDER BY label LIMIT 1');
            $fallback->execute([$this->restaurantId]);
            $newDefault = $fallback->fetchColumn() ?: 'tr';
            $this->setDefaultLanguage($newDefault);
        }

        return $this->languages();
    }

    public function setDefaultLanguage(string $code): array
    {
        $code = strtolower($code);
        $this->db->prepare('UPDATE restaurants SET language = ?, updated_at = NOW() WHERE id = ?')->execute([
            $code,
            $this->restaurantId,
        ]);

        return $this->languages();
    }

    public function currentLanguage(): string
    {
        $statement = $this->db->prepare('SELECT language FROM restaurants WHERE id = ?');
        $statement->execute([$this->restaurantId]);
        return $statement->fetchColumn() ?: 'tr';
    }

    public function currentCurrency(): string
    {
        $statement = $this->db->prepare('SELECT currency FROM restaurants WHERE id = ?');
        $statement->execute([$this->restaurantId]);
        return $statement->fetchColumn() ?: 'TRY';
    }

    public function timezones(): array
    {
        return [
            'Europe/Istanbul',
            'Europe/London',
            'Europe/Berlin',
            'Europe/Paris',
            'Europe/Amsterdam',
            'Europe/Madrid',
            'Europe/Rome',
            'Europe/Athens',
            'Europe/Moscow',
            'Asia/Dubai',
            'Asia/Tokyo',
            'Asia/Singapore',
            'Asia/Shanghai',
            'Asia/Karachi',
            'Asia/Kolkata',
            'Africa/Cairo',
            'America/New_York',
            'America/Chicago',
            'America/Los_Angeles',
            'Australia/Sydney',
        ];
    }

    private function fetchRestaurant(): array
    {
        $query = $this->db->prepare('SELECT name, phone, description, address, currency, timezone, language, theme_color, logo, favicon, qr_logo FROM restaurants WHERE id = ?');
        $query->execute([$this->restaurantId]);
        $restaurant = $query->fetch();

        if (!$restaurant) {
            $this->db->prepare('INSERT INTO restaurants (id, name) VALUES (?, ?)')->execute([$this->restaurantId, 'Yeni Restoran']);
            return $this->fetchRestaurant();
        }

        return $restaurant;
    }

    private function updateRestaurant(array $data): void
    {
        $statement = $this->db->prepare('UPDATE restaurants SET name = ?, phone = ?, description = ?, address = ?, currency = ?, timezone = ?, language = ?, theme_color = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([
            $data['name'] ?? '',
            $data['phone'] ?? null,
            $data['description'] ?? null,
            $data['address'] ?? null,
            isset($data['currency']) ? strtoupper($data['currency']) : 'TRY',
            $data['timezone'] ?? 'Europe/Istanbul',
            $data['language'] ?? 'tr',
            $data['theme_color'] ?? '#0f9d58',
            $this->restaurantId,
        ]);
    }

    private function updateBranding(array $data): void
    {
        $statement = $this->db->prepare('UPDATE restaurants SET logo = ?, favicon = ?, qr_logo = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([
            $this->normalizeMedia($data['logo'] ?? null),
            $this->normalizeMedia($data['favicon'] ?? null),
            $this->normalizeQrLogo($data['qr_logo'] ?? null),
            $this->restaurantId,
        ]);
    }

    private function updateNotifications(array $data): void
    {
        $payload = [
            'order_sound' => $this->normalizeMedia($data['order_sound'] ?? null) ?: self::DEFAULT_ORDER_SOUND,
            'waiter_sound' => $this->normalizeMedia($data['waiter_sound'] ?? null) ?: self::DEFAULT_WAITER_SOUND,
        ];

        $this->saveSection('notifications', $payload);
    }

    private function saveDailyMenu(array $items): void
    {
        $position = 1;
        $normalized = array_map(function ($item) use (&$position) {
            $item['id'] = (string)($item['id'] ?? $this->generateDailyMenuId());
            $item['product_id'] = (int)($item['product_id'] ?? 0);
            $item['headline'] = trim((string)($item['headline'] ?? ''));
            $item['tagline'] = trim((string)($item['tagline'] ?? ''));
            $item['badge'] = trim((string)($item['badge'] ?? ''));
            $item['position'] = $position++;

            return $item;
        }, array_values($items));

        $this->saveSection('daily_menu', ['items' => $normalized]);
    }

    private function getSection(string $section): array
    {
        $statement = $this->db->prepare('SELECT payload FROM restaurant_settings WHERE restaurant_id = ? AND section = ?');
        $statement->execute([$this->restaurantId, $section]);
        $payload = $statement->fetchColumn();

        return $payload ? json_decode($payload, true) : [];
    }

    private function saveSection(string $section, array $payload): void
    {
        $statement = $this->db->prepare('INSERT INTO restaurant_settings (restaurant_id, section, payload) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE payload = VALUES(payload), updated_at = NOW()');
        $statement->execute([
            $this->restaurantId,
            $section,
            json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function notifications(): array
    {
        $section = $this->getSection('notifications');
        $order = $section['order_sound'] ?? self::DEFAULT_ORDER_SOUND;
        $waiter = $section['waiter_sound'] ?? self::DEFAULT_WAITER_SOUND;

        return [
            'order_sound' => $this->mediaUrl($order),
            'waiter_sound' => $this->mediaUrl($waiter),
        ];
    }

    private function normalizeMedia(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, BASE_URL)) {
            $value = substr($value, strlen(BASE_URL));
        }

        return ltrim($value, '/');
    }

    private function normalizeQrLogo(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $value)) {
            $value = rtrim(BASE_URL, '/') . '/' . ltrim($this->normalizeMedia($value) ?? '', '/');
        }

        return $value;
    }

    private function generateDailyMenuId(): string
    {
        return bin2hex(random_bytes(6));
    }

    private function mediaUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        $value = ltrim($value, '/');
        if ($value === '') {
            return null;
        }

        return rtrim(BASE_URL, '/') . '/' . $value;
    }
}
