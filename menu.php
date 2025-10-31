<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;

$settingsService = new SettingsService();
$settings = $settingsService->all();
$defaultLanguage = $_GET['lang'] ?? ($settings['restaurant']['language'] ?? 'tr');
Language::load($defaultLanguage);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($settings['restaurant']['name'] ?? 'QR Menü') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
</head>
<body data-base-currency="<?= htmlspecialchars($settings['restaurant']['currency'] ?? 'TRY') ?>">
<header class="menu-hero">
    <div class="greeting">
        <div>
            <p>Good Morning</p>
            <h2><?= htmlspecialchars($settings['restaurant']['name'] ?? 'Guest') ?></h2>
        </div>
        <div style="display:flex;gap:12px;align-items:center;">
            <select id="languageSelect" class="form-select" style="border-radius:999px;padding:12px 18px;">
                <?php foreach (Helpers\Language::available() as $lang): ?>
                <option value="<?= $lang ?>" <?= $lang === $defaultLanguage ? 'selected' : '' ?>><?= strtoupper($lang) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="currencySelect" class="form-select" style="border-radius:999px;padding:12px 18px;">
                <?php foreach (['TRY', 'USD', 'EUR', 'GBP'] as $currency): ?>
                <option value="<?= $currency ?>" <?= ($settings['restaurant']['currency'] ?? 'TRY') === $currency ? 'selected' : '' ?>><?= $currency ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="search">
        <input type="search" id="searchMenu" placeholder="<?= htmlspecialchars(Language::get('menu.add_to_cart')) ?>" />
    </div>
</header>

<main class="menu-container">
    <section class="category-list" id="menuCategories"></section>
    <section class="product-grid" id="menuProducts"></section>
</main>

<button class="cart-floating" id="cartButton">
    <span><?= htmlspecialchars(Language::get('app.orders')) ?></span>
    <div id="cartSummary"><span>0 ürün</span><strong>0.00 <?= htmlspecialchars($settings['restaurant']['currency'] ?? 'TRY') ?></strong></div>
</button>

<button id="callWaiter" class="cart-floating" style="right:24px;left:auto;bottom:96px;background:#fbbc05;">
    <?= htmlspecialchars(Language::get('menu.call_waiter')) ?>
</button>

<script src="assets/js/menu.js"></script>
</body>
</html>
