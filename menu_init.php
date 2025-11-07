<?php
require_once __DIR__ . '/bootstrap.php';

use App\Services\SettingsService;
use Core\Database;
use Helpers\Language;

function loadMenuContext(?string $templateOverride = null): array
{
    $settingsService = new SettingsService();
    $settings = $settingsService->all();

    $restaurant = $settings['restaurant'] ?? [];
    $branding = $settings['branding'] ?? [];
    $languages = $settings['languages'] ?? [];
    $currencies = $settings['currencies'] ?? [];
    $mailSettings = $settings['mail'] ?? [];
    $notifications = $settings['notifications'] ?? [];
    $menuSettings = $settings['menu'] ?? [];
    $availableStyles = $menuSettings['styles'] ?? ($menuSettings['templates'] ?? []);

    $tableId = isset($_GET['table']) ? (int)$_GET['table'] : 0;
    $tableName = null;
    if ($tableId > 0) {
        try {
            $db = Database::connection();
            $statement = $db->prepare('SELECT name FROM tables WHERE id = ?');
            $statement->execute([$tableId]);
            $tableName = $statement->fetchColumn() ?: null;
        } catch (\Throwable $exception) {
            $tableName = null;
        }
    }

    $defaultLanguage = strtolower($_GET['lang'] ?? ($restaurant['language'] ?? 'tr'));
    Language::load($defaultLanguage);

    $defaultCurrency = strtoupper($restaurant['currency'] ?? 'TRY');
    foreach ($currencies as $currency) {
        if (!empty($currency['is_default'])) {
            $defaultCurrency = strtoupper($currency['code']);
            break;
        }
    }

    if (empty($currencies)) {
        $currencies[] = [
            'code' => $defaultCurrency,
            'name' => $defaultCurrency,
            'symbol' => '',
            'is_default' => 1,
        ];
    }

    usort($currencies, static fn(array $a, array $b) => strcmp($a['code'] ?? '', $b['code'] ?? ''));

    $currentCurrency = strtoupper($_GET['currency'] ?? $defaultCurrency);

    $orderSound = $notifications['order_sound'] ?? (rtrim(BASE_URL, '/') . '/assets/vendor/sounds/order.mp3');
    $waiterSound = $notifications['waiter_sound'] ?? (rtrim(BASE_URL, '/') . '/assets/vendor/sounds/notification.mp3');
    $friendlyPath = $tableId > 0
        ? '/menu/' . $tableId . '/' . $defaultLanguage . '/' . $currentCurrency
        : '/menu';

    $baseUrl = rtrim(BASE_URL, '/');
    $asset = static fn(string $path): string => $baseUrl . '/' . ltrim($path, '/');

    $currencyCodes = array_values(array_filter(array_map(static fn(array $currency) => strtoupper($currency['code'] ?? ''), $currencies)));
    $currencyMeta = [];
    foreach ($currencies as $currency) {
        $code = strtoupper($currency['code'] ?? '');
        if ($code === '') {
            continue;
        }
        $currencyMeta[$code] = [
            'symbol' => $currency['symbol'] ?? '',
            'name' => $currency['name'] ?? '',
        ];
    }

    $currentCurrencyCode = $currentCurrency;
    $contactEmail = trim((string)($mailSettings['notification_email'] ?? $mailSettings['from_email'] ?? ''));
    if ($contactEmail === '' && !empty($restaurant['email'])) {
        $contactEmail = trim((string)$restaurant['email']);
    }
    if ($contactEmail === '' && !empty($restaurant['contact_email'])) {
        $contactEmail = trim((string)$restaurant['contact_email']);
    }

    $templateKeys = array_values(array_filter(array_map(static fn($style) => $style['id'] ?? null, $availableStyles)));
    $selectedTemplate = $menuSettings['style'] ?? ($menuSettings['template'] ?? 'menu1');
    if ($templateOverride !== null) {
        $selectedTemplate = $templateOverride;
    }
    if (!in_array($selectedTemplate, $templateKeys, true)) {
        $selectedTemplate = $templateKeys[0] ?? 'menu1';
    }

    $templateStylesheet = $asset('assets/css/templates/' . $selectedTemplate . '.css');

    return compact(
        'settings',
        'restaurant',
        'branding',
        'languages',
        'currencies',
        'mailSettings',
        'notifications',
        'tableId',
        'tableName',
        'defaultLanguage',
        'defaultCurrency',
        'currentCurrency',
        'orderSound',
        'waiterSound',
        'friendlyPath',
        'baseUrl',
        'asset',
        'currencyCodes',
        'currencyMeta',
        'currentCurrencyCode',
        'contactEmail',
        'selectedTemplate',
        'availableStyles',
        'templateStylesheet'
    );
}
