<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;
use Core\Database;

$settingsService = new SettingsService();
$settings = $settingsService->all();
$restaurant = $settings['restaurant'] ?? [];
$branding = $settings['branding'] ?? [];
$languages = $settings['languages'] ?? [];
$currencies = $settings['currencies'] ?? [];

$tableId = isset($_GET['table']) ? (int)$_GET['table'] : 0;
$tableName = null;
if ($tableId > 0) {
    $db = Database::connection();
    $statement = $db->prepare('SELECT name FROM tables WHERE id = ?');
    $statement->execute([$tableId]);
    $tableName = $statement->fetchColumn() ?: null;
}

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
<body data-base-currency="<?= htmlspecialchars($defaultCurrency) ?>" data-current-currency="<?= htmlspecialchars($currentCurrency) ?>" data-table-id="<?= htmlspecialchars((string)$tableId) ?>">
<header class="menu-hero" style="--theme-color: <?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>;">
    <div class="greeting">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? '') ?>" style="height:64px;border-radius:16px;background:#ffffff;padding:8px;">
            <?php endif; ?>
            <div>
                <p class="mb-1">Günaydın</p>
                <h2 class="mb-0"><?= htmlspecialchars($restaurant['name'] ?? 'Misafir') ?></h2>
                <?php if ($tableName): ?>
                    <small class="d-block text-white-50">Masa: <?= htmlspecialchars($tableName) ?></small>
                <?php endif; ?>
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
    <section class="order-status" id="orderStatus">
        <div class="order-status__header">
            <h2><?= htmlspecialchars(Language::get('menu.orders_title', 'Sipariş Takibi')) ?></h2>
            <button type="button" id="refreshOrders" class="btn btn-light btn-sm">Yenile</button>
        </div>
        <div id="orderStatusList" class="order-status__list"></div>
    </section>
</main>

<button class="cart-floating" id="cartButton">
    <span><?= htmlspecialchars(Language::get('app.orders')) ?></span>
    <div id="cartSummary"><span>0 ürün</span><strong>0.00 <?= htmlspecialchars($currentCurrency) ?></strong></div>
</button>

<button id="callWaiter" class="cart-floating call-floating">
    <?= htmlspecialchars(Language::get('menu.call_waiter')) ?>
</button>

<div class="cart-backdrop d-none" id="cartBackdrop"></div>
<div class="cart-drawer d-none" id="cartDrawer">
    <div class="cart-drawer__header">
        <h3>Sepetiniz</h3>
        <button type="button" class="btn-close" id="closeCart"></button>
    </div>
    <div id="cartItems" class="cart-drawer__items"></div>
    <div class="cart-drawer__footer">
        <div>
            <span>Toplam</span>
            <strong id="cartTotal">0.00 <?= htmlspecialchars($currentCurrency) ?></strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="clearCart">Temizle</button>
            <button type="button" class="btn btn-success" id="submitOrder">Siparişi Onayla</button>
        </div>
    </div>
</div>

<div class="product-overlay d-none" id="productOverlay">
    <div class="product-sheet">
        <div class="product-sheet__header">
            <h3 id="overlayProductName"></h3>
            <button type="button" class="btn-close" id="closeProductOverlay"></button>
        </div>
        <p id="overlayProductDescription" class="text-muted"></p>
        <div id="overlayVariants" class="overlay-variants"></div>
        <div class="product-sheet__footer">
            <div class="quantity-picker">
                <button type="button" id="qtyDecrease">-</button>
                <span id="qtyValue">1</span>
                <button type="button" id="qtyIncrease">+</button>
            </div>
            <button type="button" class="btn btn-success" id="addToCartButton">Sepete Ekle</button>
        </div>
    </div>
</div>

<audio id="audioOrder" preload="auto">
    <source src="assets/vendor/sounds/order.mp3" type="audio/mpeg">
</audio>
<audio id="audioNotify" preload="auto">
    <source src="assets/vendor/sounds/notification.mp3" type="audio/mpeg">
</audio>

<script>
    window.MENU_STATE = {
        currency: <?= json_encode($currentCurrency, JSON_UNESCAPED_UNICODE) ?>,
        languages: <?= json_encode(array_column($languages, 'code'), JSON_UNESCAPED_UNICODE) ?>,
        currencies: <?= json_encode(array_column($currencies, 'code'), JSON_UNESCAPED_UNICODE) ?>,
        addToCartText: <?= json_encode(Language::get('menu.add_to_cart', 'Sepete Ekle'), JSON_UNESCAPED_UNICODE) ?>,
        tableId: <?= json_encode($tableId, JSON_UNESCAPED_UNICODE) ?>,
        tableName: <?= json_encode($tableName, JSON_UNESCAPED_UNICODE) ?>
    };
    document.documentElement.style.setProperty('--theme-color', <?= json_encode($restaurant['theme_color'] ?? '#0f9d58', JSON_UNESCAPED_UNICODE) ?>);
</script>
<script src="assets/js/menu.js"></script>
</body>
</html>
