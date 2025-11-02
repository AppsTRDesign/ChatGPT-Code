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
    $availableStyles = $menuSettings['styles'] ?? [];
    $availableViews = $menuSettings['views'] ?? [];

    $tableId = isset($_GET['table']) ? (int)$_GET['table'] : 0;
    $tableName = null;
    if ($tableId > 0) {
        $db = Database::connection();
        $statement = $db->prepare('SELECT name FROM tables WHERE id = ?');
        $statement->execute([$tableId]);
        $tableName = $statement->fetchColumn() ?: null;
    }

    $defaultLanguage = $_GET['lang'] ?? ($restaurant['language'] ?? 'tr');
    $defaultLanguage = strtolower($defaultLanguage);
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
    $currentCurrencyCode = strtoupper($currentCurrency);
    $contactEmail = trim((string)($mailSettings['notification_email'] ?? $mailSettings['from_email'] ?? ''));

    $styleKeys = array_map(static fn($style) => $style['id'] ?? null, $availableStyles);
    $styleKeys = array_filter($styleKeys);
    $viewKeys = array_map(static fn($view) => $view['id'] ?? null, $availableViews);
    $viewKeys = array_filter($viewKeys);

    $selectedStyle = $menuSettings['style'] ?? ($menuSettings['template'] ?? 'menu1');
    if ($templateOverride !== null) {
        $selectedStyle = $templateOverride;
    }
    if (!in_array($selectedStyle, $styleKeys, true)) {
        $selectedStyle = $styleKeys[0] ?? 'menu1';
    }

    $selectedView = $menuSettings['view'] ?? 'view1';
    if (!in_array($selectedView, $viewKeys, true)) {
        $selectedView = $viewKeys[0] ?? 'view1';
    }

    $styleStylesheet = $asset('assets/css/templates/' . $selectedStyle . '.css');
    $viewStylesheet = $asset('assets/css/views/' . $selectedView . '.css');
    $templateStylesheet = $styleStylesheet;

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
        'selectedStyle',
        'selectedView',
        'availableStyles',
        'availableViews',
        'styleStylesheet',
        'templateStylesheet',
        'viewStylesheet'
    );
}
