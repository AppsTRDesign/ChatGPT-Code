<?php

namespace App\Services;

use Core\Config;
use Core\Database;
use PDO;
use RuntimeException;

class SettingsService
{
    private const DEFAULT_ORDER_SOUND = 'assets/vendor/sounds/order.mp3';
    private const DEFAULT_WAITER_SOUND = 'assets/vendor/sounds/notification.mp3';
    private const DEFAULT_ORDER_FLASH_COLOR = '#0f9d58';
    private const DEFAULT_WAITER_FLASH_COLOR = '#ea4335';
    private const DEFAULT_MENU_STYLE = 'menu1';
    private const MENU_STYLES = [
        'menu1' => [
            'label' => 'Neo Fresh',
            'description' => 'Canlı yeşil vurgularla varsayılan modern tasarım.',
            'swatch' => ['#0f9d58', '#34a853'],
        ],
        'menu2' => [
            'label' => 'Gece Işığı',
            'description' => 'Koyu tonlar ve neon vurgu ile premium deneyim.',
            'swatch' => ['#1f1b2c', '#ff7b54'],
        ],
        'menu3' => [
            'label' => 'Pastel Breeze',
            'description' => 'Pastel renkler ve yumuşak kart köşeleri ile ferah görünüm.',
            'swatch' => ['#f8b195', '#355c7d'],
        ],
        'menu4' => [
            'label' => 'Minimal Beyaz',
            'description' => 'Açık alan kullanımı ve ince çizgilerle minimalist yaklaşım.',
            'swatch' => ['#f5f5f5', '#1e88e5'],
        ],
        'menu5' => [
            'label' => 'Retro Sunset',
            'description' => 'Sıcak degrade arka plan ve retro yazı tipleri ile özgün deneyim.',
            'swatch' => ['#ff9a8b', '#ff6a88'],
        ],
    ];
    private ?PDO $db = null;
    private int $restaurantId;
    private ?array $restaurantCache = null;
    private bool $fileFallback = false;
    private array $fileSettings = [];
    private string $settingsPath;

    public function __construct(int $restaurantId = 1)
    {
        $this->restaurantId = $restaurantId;

        $storage = Config::get('storage');
        $this->settingsPath = is_array($storage) && !empty($storage['settings'])
            ? $storage['settings']
            : __DIR__ . '/../../storage/settings.json';

        try {
            $this->db = Database::connection();
        } catch (RuntimeException $exception) {
            $this->fileFallback = true;
            $this->fileSettings = $this->loadFileSettings();
        }
    }

    public function all(): array
    {
        if ($this->fileFallback) {
            return $this->allFromFile();
        }

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
            'mail' => $this->mailSettings($restaurant),
            'daily_menu' => $this->dailyMenu(),
            'timezones' => $this->timezones(),
            'menu' => $this->menuSettings(),
        ];
    }

    public function update(array $data): array
    {
        $shouldRefreshQr = !empty($data['branding']) || !empty($data['qr']);
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

        if (!empty($data['mail'])) {
            $this->updateMail($data['mail']);
        }

        if (!empty($data['menu'])) {
            $this->updateMenuSettings($data['menu']);
        }

        if (array_key_exists('daily_menu', $data)) {
            $this->saveDailyMenu($data['daily_menu'] ?? []);
        }

        $updated = $this->all();

        if ($shouldRefreshQr) {
            $this->refreshTableQrCodes($updated);
        }

        return $updated;
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

    public function menuTranslations(): array
    {
        $section = $this->getSection('menu_translations');

        return [
            'categories' => $this->normalizeMenuTranslationsSection($section['categories'] ?? []),
            'products' => $this->normalizeMenuTranslationsSection($section['products'] ?? []),
            'variants' => $this->normalizeMenuTranslationsSection($section['variants'] ?? []),
        ];
    }

    public function saveMenuTranslations(array $translations): array
    {
        $payload = [
            'categories' => $this->normalizeMenuTranslationsSection($translations['categories'] ?? []),
            'products' => $this->normalizeMenuTranslationsSection($translations['products'] ?? []),
            'variants' => $this->normalizeMenuTranslationsSection($translations['variants'] ?? []),
        ];

        $this->saveSection('menu_translations', $payload);

        return $this->menuTranslations();
    }

    public function currencies(): array
    {
        if ($this->fileFallback) {
            $currencies = $this->fileSettings['currencies'] ?? [];
            $defaultCode = strtoupper($this->fetchRestaurant()['currency'] ?? 'TRY');

            if (empty($currencies)) {
                $currencies[] = [
                    'code' => $defaultCode,
                    'symbol' => '',
                    'name' => $defaultCode,
                    'is_default' => 1,
                ];
            }

            return array_map(static function ($currency) use ($defaultCode) {
                $code = strtoupper($currency['code'] ?? $defaultCode);

                return [
                    'id' => (int)($currency['id'] ?? 0),
                    'code' => $code,
                    'symbol' => $currency['symbol'] ?? '',
                    'name' => $currency['name'] ?? $code,
                    'is_default' => !empty($currency['is_default']) ? 1 : 0,
                ];
            }, $currencies);
        }

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
        if ($this->fileFallback) {
            $code = strtoupper($currency['code'] ?? '');
            if ($code === '') {
                return $this->currencies();
            }

            $currencies = $this->fileSettings['currencies'] ?? [];
            $found = false;
            foreach ($currencies as &$entry) {
                if (strtoupper($entry['code'] ?? '') === $code) {
                    $entry['code'] = $code;
                    $entry['symbol'] = $currency['symbol'] ?? ($entry['symbol'] ?? '');
                    $entry['name'] = $currency['name'] ?? ($entry['name'] ?? $code);
                    $entry['is_default'] = !empty($currency['is_default']) ? 1 : ($entry['is_default'] ?? 0);
                    $found = true;
                    break;
                }
            }
            unset($entry);

            if (!$found) {
                $currencies[] = [
                    'code' => $code,
                    'symbol' => $currency['symbol'] ?? '',
                    'name' => $currency['name'] ?? $code,
                    'is_default' => !empty($currency['is_default']) ? 1 : 0,
                ];
            }

            if (!empty($currency['is_default'])) {
                foreach ($currencies as &$entry) {
                    $entry['is_default'] = strtoupper($entry['code'] ?? '') === $code ? 1 : 0;
                }
                unset($entry);

                $restaurant = $this->fileSettings['restaurant'] ?? [];
                $restaurant['currency'] = $code;
                $this->fileSettings['restaurant'] = $restaurant;
            }

            $this->fileSettings['currencies'] = array_values($currencies);
            $this->persistFileSettings();

            return $this->currencies();
        }

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
        if ($this->fileFallback) {
            $currencies = $this->fileSettings['currencies'] ?? [];
            $codeToRemove = null;

            foreach ($currencies as $index => $currency) {
                $currencyId = (int)($currency['id'] ?? $index);
                if ($currencyId === $id) {
                    $codeToRemove = strtoupper($currency['code'] ?? '');
                    unset($currencies[$index]);
                    break;
                }
            }

            if ($codeToRemove === null && isset($currencies[$id])) {
                $codeToRemove = strtoupper($currencies[$id]['code'] ?? '');
                unset($currencies[$id]);
            }

            $currencies = array_values($currencies);
            if (empty($currencies)) {
                $defaultCode = strtoupper($this->fetchRestaurant()['currency'] ?? 'TRY');
                $currencies[] = [
                    'code' => $defaultCode,
                    'symbol' => '',
                    'name' => $defaultCode,
                    'is_default' => 1,
                ];
            }

            $hasDefault = false;
            foreach ($currencies as $currency) {
                if (!empty($currency['is_default'])) {
                    $hasDefault = true;
                    break;
                }
            }

            if (!$hasDefault && $currencies) {
                $currencies[0]['is_default'] = 1;
                $restaurant = $this->fileSettings['restaurant'] ?? [];
                $restaurant['currency'] = strtoupper($currencies[0]['code'] ?? 'TRY');
                $this->fileSettings['restaurant'] = $restaurant;
            }

            $this->fileSettings['currencies'] = $currencies;
            $this->persistFileSettings();

            return $this->currencies();
        }

        $statement = $this->db->prepare('DELETE FROM restaurant_currencies WHERE restaurant_id = ? AND id = ?');
        $statement->execute([$this->restaurantId, $id]);

        return $this->currencies();
    }

    public function setDefaultCurrency(string $code): array
    {
        if ($this->fileFallback) {
            $code = strtoupper($code);
            $currencies = $this->fileSettings['currencies'] ?? [];
            $hasMatch = false;

            foreach ($currencies as &$currency) {
                $match = strtoupper($currency['code'] ?? '') === $code;
                $currency['is_default'] = $match ? 1 : 0;
                $hasMatch = $hasMatch || $match;
            }
            unset($currency);

            if ($hasMatch) {
                $restaurant = $this->fileSettings['restaurant'] ?? [];
                $restaurant['currency'] = $code;
                $this->fileSettings['restaurant'] = $restaurant;
            }

            $this->fileSettings['currencies'] = $currencies;
            $this->persistFileSettings();

            return $this->currencies();
        }

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
        if ($this->fileFallback) {
            $languages = $this->fileSettings['languages'] ?? [];
            $default = strtolower($this->fetchRestaurant()['language'] ?? 'tr');

            if (empty($languages)) {
                $files = glob(LANG_PATH . '/*.json') ?: [];
                foreach ($files as $file) {
                    $code = strtolower(basename($file, '.json'));
                    $languages[] = [
                        'code' => $code,
                        'label' => strtoupper($code),
                        'is_default' => $code === $default ? 1 : 0,
                    ];
                }
            }

            if (empty($languages)) {
                $languages[] = [
                    'code' => $default,
                    'label' => strtoupper($default),
                    'is_default' => 1,
                ];
            }

            return array_map(static function ($language) use ($default) {
                $code = strtolower($language['code'] ?? $default);
                return [
                    'code' => $code,
                    'label' => $language['label'] ?? strtoupper($code),
                    'is_default' => !empty($language['is_default']) ? 1 : 0,
                ];
            }, $languages);
        }

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
        if ($this->fileFallback) {
            $code = strtolower($code);
            $languages = $this->fileSettings['languages'] ?? [];
            $found = false;
            foreach ($languages as &$language) {
                if (strtolower($language['code'] ?? '') === $code) {
                    $language['label'] = $label;
                    $found = true;
                    break;
                }
            }
            unset($language);

            if (!$found) {
                $languages[] = [
                    'code' => $code,
                    'label' => $label,
                    'is_default' => 0,
                ];
            }

            $this->fileSettings['languages'] = $languages;
            $this->persistFileSettings();

            return $this->languages();
        }

        $statement = $this->db->prepare('INSERT INTO restaurant_languages (restaurant_id, code, label) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE label = VALUES(label)');
        $statement->execute([$this->restaurantId, strtolower($code), $label]);

        return $this->languages();
    }

    public function deleteLanguage(string $code): array
    {
        if ($this->fileFallback) {
            $code = strtolower($code);
            $languages = $this->fileSettings['languages'] ?? [];
            $languages = array_values(array_filter($languages, static fn($language) => strtolower($language['code'] ?? '') !== $code));

            $this->fileSettings['languages'] = $languages;
            if ($this->currentLanguage() === $code && $languages) {
                $this->setDefaultLanguage($languages[0]['code']);
            }

            $this->persistFileSettings();

            return $this->languages();
        }

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
        if ($this->fileFallback) {
            $code = strtolower($code);
            $languages = $this->fileSettings['languages'] ?? [];
            foreach ($languages as &$language) {
                $language['is_default'] = strtolower($language['code'] ?? '') === $code ? 1 : 0;
            }
            unset($language);

            $this->fileSettings['languages'] = $languages;
            $restaurant = $this->fileSettings['restaurant'] ?? [];
            $restaurant['language'] = $code;
            $this->fileSettings['restaurant'] = $restaurant;
            $this->persistFileSettings();

            return $this->languages();
        }

        $code = strtolower($code);
        $this->db->prepare('UPDATE restaurants SET language = ?, updated_at = NOW() WHERE id = ?')->execute([
            $code,
            $this->restaurantId,
        ]);

        return $this->languages();
    }

    public function currentLanguage(): string
    {
        if ($this->fileFallback) {
            return strtolower($this->fetchRestaurant()['language'] ?? 'tr');
        }

        $statement = $this->db->prepare('SELECT language FROM restaurants WHERE id = ?');
        $statement->execute([$this->restaurantId]);
        return $statement->fetchColumn() ?: 'tr';
    }

    public function currentCurrency(): string
    {
        if ($this->fileFallback) {
            return strtoupper($this->fetchRestaurant()['currency'] ?? 'TRY');
        }

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

    private function menuSettings(): array
    {
        $section = $this->getSection('menu');
        $style = $this->normalizeMenuTemplate($section['style'] ?? ($section['template'] ?? null));

        $styles = [];
        foreach (self::MENU_STYLES as $key => $meta) {
            $styles[] = [
                'id' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'swatch' => $meta['swatch'],
            ];
        }

        return [
            'style' => $style,
            'styles' => $styles,
            'template' => $style,
            'templates' => $styles,
        ];
    }

    private function fetchRestaurant(): array
    {
        if ($this->restaurantCache !== null) {
            return $this->restaurantCache;
        }

        if ($this->fileFallback) {
            $defaults = [
                'name' => 'QR Menü',
                'phone' => '',
                'description' => '',
                'address' => '',
                'currency' => 'TRY',
                'timezone' => 'Europe/Istanbul',
                'language' => 'tr',
                'theme_color' => '#0f9d58',
            ];

            $restaurant = array_merge($defaults, $this->fileSettings['restaurant'] ?? []);
            $this->restaurantCache = $restaurant;

            return $restaurant;
        }

        $query = $this->db->prepare('SELECT name, phone, description, address, currency, timezone, language, theme_color, logo, favicon, qr_logo FROM restaurants WHERE id = ?');
        $query->execute([$this->restaurantId]);
        $restaurant = $query->fetch();

        if (!$restaurant) {
            $this->db->prepare('INSERT INTO restaurants (id, name) VALUES (?, ?)')->execute([$this->restaurantId, 'Yeni Restoran']);
            return $this->fetchRestaurant();
        }

        $this->restaurantCache = $restaurant;

        return $restaurant;
    }

    private function updateRestaurant(array $data): void
    {
        if ($this->fileFallback) {
            $restaurant = $this->fetchRestaurant();
            $restaurant['name'] = $data['name'] ?? $restaurant['name'];
            $restaurant['phone'] = $data['phone'] ?? $restaurant['phone'];
            $restaurant['description'] = $data['description'] ?? $restaurant['description'];
            $restaurant['address'] = $data['address'] ?? $restaurant['address'];
            $restaurant['currency'] = isset($data['currency']) ? strtoupper($data['currency']) : ($restaurant['currency'] ?? 'TRY');
            $restaurant['timezone'] = $data['timezone'] ?? ($restaurant['timezone'] ?? 'Europe/Istanbul');
            $restaurant['language'] = $data['language'] ?? ($restaurant['language'] ?? 'tr');
            $restaurant['theme_color'] = $data['theme_color'] ?? ($restaurant['theme_color'] ?? '#0f9d58');

            $this->fileSettings['restaurant'] = $restaurant;
            $this->restaurantCache = $restaurant;
            $this->persistFileSettings();

            return;
        }

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
        $this->restaurantCache = null;
    }

    private function updateBranding(array $data): void
    {
        if ($this->fileFallback) {
            $restaurant = $this->fetchRestaurant();
            if (array_key_exists('logo', $data)) {
                $restaurant['logo'] = $this->normalizeMedia($data['logo']);
            }
            if (array_key_exists('favicon', $data)) {
                $restaurant['favicon'] = $this->normalizeMedia($data['favicon']);
            }
            if (array_key_exists('qr_logo', $data)) {
                $restaurant['qr_logo'] = $this->normalizeQrLogo($data['qr_logo']);
            }

            $this->fileSettings['restaurant'] = $restaurant;
            $this->restaurantCache = $restaurant;
            $this->persistFileSettings();

            return;
        }

        $statement = $this->db->prepare('UPDATE restaurants SET logo = ?, favicon = ?, qr_logo = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([
            $this->normalizeMedia($data['logo'] ?? null),
            $this->normalizeMedia($data['favicon'] ?? null),
            $this->normalizeQrLogo($data['qr_logo'] ?? null),
            $this->restaurantId,
        ]);
        $this->restaurantCache = null;
    }

    private function updateNotifications(array $data): void
    {
        $payload = [
            'order_sound' => $this->normalizeMedia($data['order_sound'] ?? null) ?: self::DEFAULT_ORDER_SOUND,
            'waiter_sound' => $this->normalizeMedia($data['waiter_sound'] ?? null) ?: self::DEFAULT_WAITER_SOUND,
            'flash_enabled' => filter_var($data['flash_enabled'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false,
            'flash_order_color' => $this->sanitizeColor($data['flash_order_color'] ?? null, self::DEFAULT_ORDER_FLASH_COLOR),
            'flash_waiter_color' => $this->sanitizeColor($data['flash_waiter_color'] ?? null, self::DEFAULT_WAITER_FLASH_COLOR),
        ];

        $this->saveSection('notifications', $payload);
    }

    private function updateMail(array $data): void
    {
        $payload = [
            'from_name' => trim((string)($data['from_name'] ?? '')),
            'from_email' => $this->normalizeEmail($data['from_email'] ?? ''),
            'notification_email' => $this->normalizeEmail($data['notification_email'] ?? ''),
            'reply_to' => $this->normalizeEmail($data['reply_to'] ?? ''),
        ];

        if (empty($payload['from_name'])) {
            $restaurant = $this->fetchRestaurant();
            $payload['from_name'] = $restaurant['name'] ?? 'QR Menü';
        }

        if (!$payload['from_email'] && $payload['notification_email']) {
            $payload['from_email'] = $payload['notification_email'];
        }

        if (!$payload['notification_email']) {
            $payload['notification_email'] = $this->primaryUserEmail();
        }

        if (!$payload['reply_to']) {
            $payload['reply_to'] = $payload['notification_email'];
        }

        $this->saveSection('mail', array_filter($payload, static fn($value) => $value !== null && $value !== ''));
    }

    private function updateMenuSettings(array $data): void
    {
        $style = $this->normalizeMenuTemplate($data['style'] ?? ($data['template'] ?? null));

        $this->saveSection('menu', [
            'style' => $style,
            'template' => $style,
        ]);
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

    private function normalizeMenuTranslationsSection(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $id => $translations) {
            $key = (string)$id;
            if (!is_array($translations)) {
                continue;
            }

            $clean = [];
            foreach ($translations as $code => $fields) {
                if (!is_array($fields)) {
                    continue;
                }
                $codeKey = strtolower((string)$code);
                $fieldValues = [];
                foreach ($fields as $field => $value) {
                    if ($value === null) {
                        continue;
                    }
                    $fieldValues[$field] = is_scalar($value) ? (string)$value : '';
                }
                if (!empty($fieldValues)) {
                    $clean[$codeKey] = $fieldValues;
                }
            }

            if (!empty($clean)) {
                $normalized[$key] = $clean;
            }
        }

        return $normalized;
    }

    private function getSection(string $section): array
    {
        if ($this->fileFallback) {
            $value = $this->fileSettings[$section] ?? [];
            return is_array($value) ? $value : [];
        }

        $statement = $this->db->prepare('SELECT payload FROM restaurant_settings WHERE restaurant_id = ? AND section = ?');
        $statement->execute([$this->restaurantId, $section]);
        $payload = $statement->fetchColumn();

        return $payload ? json_decode($payload, true) : [];
    }

    private function saveSection(string $section, array $payload): void
    {
        if ($this->fileFallback) {
            $this->fileSettings[$section] = $payload;
            $this->persistFileSettings();
            return;
        }

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
        $flash = filter_var($section['flash_enabled'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $orderColor = $this->sanitizeColor($section['flash_order_color'] ?? null, self::DEFAULT_ORDER_FLASH_COLOR);
        $waiterColor = $this->sanitizeColor($section['flash_waiter_color'] ?? null, self::DEFAULT_WAITER_FLASH_COLOR);

        return [
            'order_sound' => $this->mediaUrl($order),
            'waiter_sound' => $this->mediaUrl($waiter),
            'flash_enabled' => $flash ?? false,
            'flash_order_color' => $orderColor,
            'flash_waiter_color' => $waiterColor,
        ];
    }

    public function mailSettings(?array $restaurant = null): array
    {
        $section = $this->getSection('mail');
        $restaurant ??= $this->fetchRestaurant();

        $fromName = trim((string)($section['from_name'] ?? ($restaurant['name'] ?? 'QR Menü')));
        $fromEmail = $this->normalizeEmail($section['from_email'] ?? '') ?? ($this->normalizeEmail($section['notification_email'] ?? '') ?? null);
        $notification = $this->normalizeEmail($section['notification_email'] ?? '') ?? $this->primaryUserEmail();
        $replyTo = $this->normalizeEmail($section['reply_to'] ?? '') ?? $notification;

        return [
            'from_name' => $fromName !== '' ? $fromName : ($restaurant['name'] ?? 'QR Menü'),
            'from_email' => $fromEmail ?? '',
            'notification_email' => $notification ?? '',
            'reply_to' => $replyTo ?? '',
        ];
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(strtolower($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function primaryUserEmail(): ?string
    {
        if ($this->fileFallback) {
            $mail = $this->fileSettings['mail'] ?? [];
            $email = $mail['notification_email'] ?? ($mail['from_email'] ?? null);
            return $this->normalizeEmail($email);
        }

        $statement = $this->db->prepare('SELECT email FROM restaurant_users WHERE restaurant_id = ? ORDER BY id ASC LIMIT 1');
        $statement->execute([$this->restaurantId]);
        $email = $statement->fetchColumn();

        return $this->normalizeEmail($email ?: null);
    }

    private function refreshTableQrCodes(array $settings): void
    {
        if ($this->fileFallback) {
            return;
        }

        $restaurant = $settings['restaurant'] ?? [];
        $qrConfig = $settings['qr'] ?? [];
        $branding = $settings['branding'] ?? [];
        $logo = $branding['qr_logo'] ?? ($qrConfig['logo_url'] ?? ($qrConfig['logo'] ?? null));
        if ($logo) {
            $qrConfig['logo_url'] = $logo;
        }

        $language = strtolower($restaurant['language'] ?? $this->currentLanguage());
        $currency = strtoupper($restaurant['currency'] ?? $this->currentCurrency());

        $qrService = new QrService();
        $statement = $this->db->prepare('SELECT id FROM tables WHERE restaurant_id = ?');
        $statement->execute([$this->restaurantId]);
        $tableIds = $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($tableIds as $tableId) {
            $tableId = (int)$tableId;
            $tableUrl = rtrim(BASE_URL, '/') . '/menu/' . $tableId . '/' . $language . '/' . $currency;
            $qrUrl = $qrService->generateUrl($tableUrl, $qrConfig);
            $update = $this->db->prepare('UPDATE tables SET qr_code_url = ?, updated_at = NOW() WHERE id = ? AND restaurant_id = ?');
            $update->execute([$qrUrl, $tableId, $this->restaurantId]);
        }
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

    private function sanitizeColor(?string $color, string $fallback): string
    {
        if (!$color) {
            return strtoupper($fallback);
        }

        $value = strtoupper(trim($color));
        if ($value === '') {
            return strtoupper($fallback);
        }

        if (!str_starts_with($value, '#')) {
            $value = '#' . ltrim($value, '#');
        }

        if (preg_match('/^#([0-9A-F]{3}|[0-9A-F]{6})$/', $value)) {
            return $value;
        }

        return strtoupper($fallback);
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

    private function normalizeMenuTemplate(?string $template): string
    {
        $key = strtolower(trim((string)$template));
        if ($key === '') {
            $key = self::DEFAULT_MENU_STYLE;
        }

        if (!array_key_exists($key, self::MENU_STYLES)) {
            $key = self::DEFAULT_MENU_STYLE;
        }

        return $key;
    }

    private function allFromFile(): array
    {
        $restaurant = $this->fetchRestaurant();
        $branding = [
            'logo' => $this->mediaUrl($restaurant['logo'] ?? null),
            'favicon' => $this->mediaUrl($restaurant['favicon'] ?? null),
            'qr_logo' => $this->mediaUrl($restaurant['qr_logo'] ?? null),
        ];

        $restaurantForOutput = $restaurant;
        $restaurantForOutput['logo'] = $branding['logo'];
        $restaurantForOutput['favicon'] = $branding['favicon'];
        $restaurantForOutput['qr_logo'] = $branding['qr_logo'];

        $qr = $this->getSection('qr');
        if (!empty($branding['qr_logo'])) {
            $qr['logo_url'] = $branding['qr_logo'];
            $qr['logo'] = $branding['qr_logo'];
        } elseif (!empty($qr['logo']) && empty($qr['logo_url'])) {
            $qr['logo_url'] = $this->mediaUrl($qr['logo']);
        }

        return [
            'restaurant' => $restaurantForOutput,
            'qr' => $qr,
            'branding' => $branding,
            'currencies' => $this->currencies(),
            'languages' => $this->languages(),
            'notifications' => $this->notifications(),
            'mail' => $this->mailSettings($restaurantForOutput),
            'daily_menu' => $this->dailyMenu(),
            'timezones' => $this->timezones(),
            'menu' => $this->menuSettings(),
        ];
    }

    private function loadFileSettings(): array
    {
        if (!is_file($this->settingsPath)) {
            return [];
        }

        $contents = file_get_contents($this->settingsPath);
        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function persistFileSettings(): void
    {
        if (!$this->settingsPath) {
            return;
        }

        $directory = dirname($this->settingsPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $this->settingsPath,
            json_encode($this->fileSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
