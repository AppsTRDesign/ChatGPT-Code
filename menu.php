<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;

$settingsService = new SettingsService();
$settings = $settingsService->all();
$restaurant = $settings['restaurant'] ?? [];
$branding = $settings['branding'] ?? [];
$languages = $settings['languages'] ?? [];
$currencies = $settings['currencies'] ?? [];

$defaultLanguage = $_GET['lang'] ?? ($restaurant['language'] ?? 'tr');
Language::load($defaultLanguage);

$defaultCurrency = $restaurant['currency'] ?? 'TRY';
foreach ($currencies as $currency) {
    if (!empty($currency['is_default'])) {
        $defaultCurrency = $currency['code'];
        break;
    }
}

$currentCurrency = $_GET['currency'] ?? $defaultCurrency;

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'QR Menü') ?></title>
    <?php if (!empty($branding['favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($branding['favicon']) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
</head>
<body data-base-currency="<?= htmlspecialchars($defaultCurrency) ?>" data-current-currency="<?= htmlspecialchars($currentCurrency) ?>">
<header class="menu-hero" style="--theme-color: <?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>;">
    <div class="greeting">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? '') ?>" style="height:64px;border-radius:16px;background:#ffffff;padding:8px;">
            <?php endif; ?>
            <div>
                <p class="mb-1">Günaydın</p>
                <h2 class="mb-0"><?= htmlspecialchars($restaurant['name'] ?? 'Misafir') ?></h2>
            </div>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <select id="languageSelect" class="form-select" style="border-radius:999px;padding:12px 18px;min-width:120px;">
                <?php foreach ($languages as $language): ?>
                    <option value="<?= htmlspecialchars($language['code']) ?>" <?= $language['code'] === $defaultLanguage ? 'selected' : '' ?>><?= htmlspecialchars(strtoupper($language['code'])) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="currencySelect" class="form-select" style="border-radius:999px;padding:12px 18px;min-width:120px;">
                <?php foreach ($currencies as $currency): ?>
                    <option value="<?= htmlspecialchars($currency['code']) ?>" <?= $currency['code'] === $currentCurrency ? 'selected' : '' ?>><?= htmlspecialchars($currency['code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="search">
        <input type="search" id="searchMenu" placeholder="<?= htmlspecialchars(Language::get('menu.search', 'Menüde ara')) ?>" />
    </div>
</header>

<main class="menu-container">
    <section class="category-list" id="menuCategories"></section>
    <section class="product-grid" id="menuProducts"></section>
</main>

<button class="cart-floating" id="cartButton">
    <span><?= htmlspecialchars(Language::get('app.orders')) ?></span>
    <div id="cartSummary"><span>0 ürün</span><strong>0.00 <?= htmlspecialchars($currentCurrency) ?></strong></div>
</button>

<button id="callWaiter" class="cart-floating" style="right:24px;left:auto;bottom:96px;background:#fbbc05;">
    <?= htmlspecialchars(Language::get('menu.call_waiter')) ?>
</button>

<script>
    window.MENU_STATE = {
        currency: <?= json_encode($currentCurrency, JSON_UNESCAPED_UNICODE) ?>,
        languages: <?= json_encode(array_column($languages, 'code'), JSON_UNESCAPED_UNICODE) ?>,
        currencies: <?= json_encode(array_column($currencies, 'code'), JSON_UNESCAPED_UNICODE) ?>,
        addToCartText: <?= json_encode(Language::get('menu.add_to_cart', 'Sepete Ekle'), JSON_UNESCAPED_UNICODE) ?>
    };
    document.documentElement.style.setProperty('--theme-color', <?= json_encode($restaurant['theme_color'] ?? '#0f9d58', JSON_UNESCAPED_UNICODE) ?>);
</script>
<script src="assets/js/menu.js"></script>
</body>
</html>
