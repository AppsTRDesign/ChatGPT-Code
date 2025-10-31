<?php

namespace App\Services;

use Core\Database;
use PDO;

class SettingsService
{
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
            $qr['logo'] = $branding['qr_logo'];
        }

        return [
            'restaurant' => $restaurant,
            'qr' => $qr,
            'branding' => $branding,
            'currencies' => $this->currencies(),
            'languages' => $this->languages(),
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

        return $this->all();
    }

    public function currencies(): array
    {
        $query = $this->db->prepare('SELECT id, code, symbol, name, is_default FROM restaurant_currencies WHERE restaurant_id = ? ORDER BY is_default DESC, name ASC');
        $query->execute([$this->restaurantId]);
        return $query->fetchAll() ?: [];
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

        return $this->currencies();
    }

    public function languages(): array
    {
        $statement = $this->db->prepare('SELECT code, label FROM restaurant_languages WHERE restaurant_id = ? ORDER BY label');
        $statement->execute([$this->restaurantId]);
        return $statement->fetchAll() ?: [];
    }

    public function saveLanguageMeta(string $code, string $label): array
    {
        $statement = $this->db->prepare('INSERT INTO restaurant_languages (restaurant_id, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)');
        $statement->execute([$this->restaurantId, strtolower($code), $label]);

        return $this->languages();
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
            $data['currency'] ?? 'TRY',
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
