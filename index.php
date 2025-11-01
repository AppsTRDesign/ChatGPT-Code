<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;
use App\Services\AuthService;
use App\Services\QrService;
use Core\Config;

$authService = new AuthService();
$user = $authService->user();

if (!$user) {
    header('Location: login.php');
    exit;
}

$settingsService = new SettingsService();
$settings = $settingsService->all();
$restaurant = $settings['restaurant'] ?? [];
$branding = $settings['branding'] ?? [];
$notifications = $settings['notifications'] ?? [];
$mailSettings = $settings['mail'] ?? [];
$currencies = $settings['currencies'] ?? [];
usort($currencies, static fn(array $a, array $b) => strcmp($a['code'] ?? '', $b['code'] ?? ''));
$timezones = $settings['timezones'] ?? $settingsService->timezones();
$defaultLanguage = $restaurant['language'] ?? 'tr';
Language::load($defaultLanguage);

$allowedSections = ['dashboard', 'orders', 'tables', 'waiter', 'menu', 'reports', 'settings'];
$currentSection = $_GET['section'] ?? 'dashboard';
if (!in_array($currentSection, $allowedSections, true)) {
    $currentSection = 'dashboard';
}

$sectionPaths = [
    'dashboard' => '/panel',
    'orders' => '/panel/orders',
    'tables' => '/panel/tables',
    'waiter' => '/panel/waiter-calls',
    'menu' => '/panel/menu',
    'reports' => '/panel/reports',
    'settings' => '/panel/settings',
];

$navItems = [
    ['key' => 'dashboard', 'label' => Language::get('app.dashboard')],
    ['key' => 'orders', 'label' => Language::get('app.orders')],
    ['key' => 'tables', 'label' => Language::get('app.tables')],
    ['key' => 'waiter', 'label' => Language::get('dashboard.waiter_calls')],
    ['key' => 'menu', 'label' => Language::get('menu.manager', 'Menü Yönetimi')],
    ['key' => 'reports', 'label' => Language::get('app.reports')],
    ['key' => 'settings', 'label' => Language::get('app.settings')],
];

$qrSettings = $settings['qr'] ?? [];
$qrLogo = $branding['qr_logo'] ?? ($qrSettings['logo'] ?? null);
if ($qrLogo && str_starts_with($qrLogo, '/')) {
    $qrLogo = rtrim(BASE_URL, '/') . $qrLogo;
}
$qrPreview = (new QrService())->generateUrl(BASE_URL . '/menu', array_merge($qrSettings, ['logo' => $qrLogo]));

$orderSound = $notifications['order_sound'] ?? (rtrim(BASE_URL, '/') . '/assets/vendor/sounds/order.mp3');
$waiterSound = $notifications['waiter_sound'] ?? (rtrim(BASE_URL, '/') . '/assets/vendor/sounds/notification.mp3');
$selectedTimezone = $restaurant['timezone'] ?? 'Europe/Istanbul';

$reportStart = (new DateTimeImmutable('-6 days'))->format('Y-m-d');
$reportEnd = (new DateTimeImmutable('now'))->format('Y-m-d');

$baseUrl = rtrim(BASE_URL, '/');
$asset = static fn(string $path): string => $baseUrl . '/' . ltrim($path, '/');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(Language::get('app.title', 'NoaSoft QR Menü')) ?></title>
    <?php if (!empty($branding['favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($branding['favicon']) ?>">
    <?php endif; ?>
    <meta name="theme-color" content="<?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-borderless/borderless.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.0.3/r-3.0.1/datatables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-M9d1RChESyqCCpt5TR1+t0NenE2no0RvrRZtGJPD7W82dManIeZDV4SSQdlqzTeWY5Avzk3l3pNGdisM8z7jkQ==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/style.css')) ?>">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.0.3/r-3.0.1/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script>if (window.Dropzone) { window.Dropzone.autoDiscover = false; }</script>
    <script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
</head>
<body data-base-url="<?= htmlspecialchars($baseUrl) ?>">
<div class="dashboard" data-theme-color="<?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>">
    <aside class="dashboard__sidebar d-none d-lg-flex">
        <div class="dashboard__brand">
            <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?>" class="dashboard__logo">
            <?php else: ?>
                <h1><?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?></h1>
            <?php endif; ?>
        </div>
        <nav class="dashboard__nav">
            <?php foreach ($navItems as $item): ?>
                <?php $key = $item['key']; ?>
                <a href="<?= htmlspecialchars($sectionPaths[$key] ?? '#') ?>"
                   class="dashboard__link <?= $currentSection === $key ? 'active' : '' ?>"
                   data-section="<?= htmlspecialchars($key) ?>"
                   data-url="<?= htmlspecialchars($sectionPaths[$key] ?? '#') ?>">
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="dashboard__user">
            <div>
                <span class="dashboard__user-name"><?= htmlspecialchars($user['name']) ?></span>
                <small class="d-block text-muted"><?= htmlspecialchars($user['email']) ?></small>
            </div>
            <button class="btn btn-sm btn-outline-light" id="logoutButton">Çıkış</button>
        </div>
    </aside>
    <div class="dashboard__overlay d-lg-none" id="sidebarOverlay"></div>
    <div class="alert-flash" id="alertFlash" role="alert" aria-hidden="true">
        <div class="alert-flash__content">
            <span id="alertFlashText">Yeni bildirim</span>
        </div>
    </div>
    <main class="dashboard__content">
        <nav class="navbar navbar-light dashboard__mobile-header d-lg-none">
            <div class="container-fluid">
                <span class="navbar-brand">
                    <?php if (!empty($branding['logo'])): ?>
                        <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?>" class="dashboard__mobile-logo">
                    <?php endif; ?>
                    <span class="navbar-brand__title"><?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?></span>
                </span>
                <button type="button" class="navbar-toggler dashboard__menu-toggle" id="sidebarToggle" aria-label="Menüyü Aç" aria-expanded="false" aria-controls="mobileNav">
                    <span class="navbar-toggler-icon"></span>
                    <span class="dashboard__toggle-label" data-toggle-label>Menüyü Aç</span>
                </button>
            </div>
        </nav>
        <div class="collapse dashboard__mobile-collapse d-lg-none" id="mobileNav">
            <div class="dashboard__mobile-menu">
                <?php foreach ($navItems as $item): ?>
                    <?php $key = $item['key']; ?>
                    <a href="<?= htmlspecialchars($sectionPaths[$key] ?? '#') ?>"
                       class="dashboard__link <?= $currentSection === $key ? 'active' : '' ?>"
                       data-section="<?= htmlspecialchars($key) ?>"
                       data-url="<?= htmlspecialchars($sectionPaths[$key] ?? '#') ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="dashboard__mobile-user">
                <div>
                    <span class="dashboard__user-name"><?= htmlspecialchars($user['name']) ?></span>
                    <small class="d-block text-muted"><?= htmlspecialchars($user['email']) ?></small>
                </div>
                <button class="btn btn-sm btn-outline-success" id="mobileLogout">Çıkış</button>
            </div>
        </div>
        <section class="section<?= $currentSection === 'dashboard' ? '' : ' d-none' ?>" id="section-dashboard">
            <div class="row g-3 mb-4" id="summaryCards">
                <div class="col-6 col-md-3">
                    <div class="summary-card" data-summary="total_orders">
                        <h3><?= htmlspecialchars(Language::get('dashboard.total_orders')) ?></h3>
                        <strong>0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-card" data-summary="revenue">
                        <h3><?= htmlspecialchars(Language::get('dashboard.revenue')) ?></h3>
                        <strong>0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-card" data-summary="active_tables">
                        <h3><?= htmlspecialchars(Language::get('dashboard.active_tables')) ?></h3>
                        <strong>0</strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="summary-card" data-summary="waiter_calls">
                        <h3><?= htmlspecialchars(Language::get('dashboard.waiter_calls')) ?></h3>
                        <strong>0</strong>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <span><?= htmlspecialchars(Language::get('dashboard.orders_chart')) ?></span>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" data-report="daily">Günlük</button>
                        <button class="btn btn-sm btn-outline-primary active" data-report="weekly">Haftalık</button>
                        <button class="btn btn-sm btn-outline-primary" data-report="monthly">Aylık</button>
                        <button class="btn btn-sm btn-outline-primary" data-report="yearly">Yıllık</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'orders' ? '' : ' d-none' ?>" id="section-orders">
            <div class="card">
                <div class="card-header flex-column flex-lg-row d-flex gap-3 align-items-lg-center justify-content-between">
                    <div>
                        <h2 class="h5 mb-1">Gelen Siparişler</h2>
                        <p class="text-muted mb-0">Durumları güncelleyebilir, adisyon çıktısı alabilirsiniz.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="btn-group" id="orderStatusFilters" role="group">
                            <button class="btn btn-sm btn-outline-secondary active" data-status="all">Tümü</button>
                            <button class="btn btn-sm btn-outline-secondary" data-status="Beklemede">Beklemede</button>
                            <button class="btn btn-sm btn-outline-secondary" data-status="Hazırlanıyor">Hazırlanıyor</button>
                            <button class="btn btn-sm btn-outline-secondary" data-status="Hazırlandı">Hazırlandı</button>
                            <button class="btn btn-sm btn-outline-secondary" data-status="Ödeme Alındı">Ödeme Alındı</button>
                            <button class="btn btn-sm btn-outline-secondary" data-status="İptal">İptal</button>
                        </div>
                        <input type="search" id="orderSearch" class="form-control form-control-sm" placeholder="Sipariş veya masa ara">
                    </div>
                </div>
                <div class="card-body">
                    <div id="ordersContainer" class="card-stack"></div>
                    <nav id="ordersPagination" class="pagination-bar mt-3"></nav>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'tables' ? '' : ' d-none' ?>" id="section-tables">
            <div class="card">
                <div class="card-header flex-column flex-lg-row d-flex gap-3 align-items-lg-center justify-content-between">
                    <div>
                        <h2 class="h5 mb-1">Masalar ve QR Kodlar</h2>
                        <p class="text-muted mb-0">Masaları düzenleyin, bağlantıları ve aktif siparişleri takip edin.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="search" id="tableSearch" class="form-control form-control-sm" placeholder="Masa ara">
                        <button class="btn btn-sm btn-primary" id="newTableButton">Yeni Masa</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="tablesContainer" class="grid-responsive"></div>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'waiter' ? '' : ' d-none' ?>" id="section-waiter">
            <div class="card">
                <div class="card-header flex-column flex-lg-row d-flex gap-3 align-items-lg-center justify-content-between">
                    <div>
                        <h2 class="h5 mb-1">Garson Çağrıları</h2>
                        <p class="text-muted mb-0">Çağrıları listeden durumlarına göre yönetin.</p>
                    </div>
                    <input type="search" id="waiterSearch" class="form-control form-control-sm" placeholder="Masa veya durum ara">
                </div>
                <div class="card-body">
                    <div id="waiterContainer" class="waiter-list"></div>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'menu' ? '' : ' d-none' ?>" id="section-menu">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card h-100">
                        <div class="card-header flex-column flex-lg-row d-flex gap-3 align-items-lg-center justify-content-between">
                            <div>
                                <h2 class="h5 mb-1">Günün Menüsü</h2>
                                <p class="text-muted mb-0">Öne çıkan ürünleri seçin ve kaydırmalı menüde gösterin.</p>
                            </div>
                            <button class="btn btn-sm btn-primary" id="newDailyMenuButton">Öğe Ekle</button>
                        </div>
                        <div class="card-body">
                            <div id="dailyMenuList" class="daily-menu-admin"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h5 mb-1">Kategoriler</h2>
                                <p class="text-muted mb-0">Icon seçebilir veya görsel yükleyebilirsiniz.</p>
                            </div>
                            <button class="btn btn-sm btn-primary" id="newCategoryButton">Kategori Ekle</button>
                        </div>
                        <div class="card-body">
                            <div id="categoryList" class="category-list-admin"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="card h-100">
                        <div class="card-header flex-column flex-lg-row d-flex gap-3 align-items-lg-center justify-content-between">
                            <div>
                                <h2 class="h5 mb-1">Ürünler</h2>
                                <p class="text-muted mb-0">Varyasyonları ile birlikte düzenleyin.</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <select id="productCategoryFilter" class="form-select form-select-sm" style="min-width: 180px;"></select>
                                <input type="search" id="productSearch" class="form-control form-control-sm" placeholder="Ürün ara">
                                <button class="btn btn-sm btn-primary" id="newProductButton">Ürün Ekle</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="productList" class="product-list-admin"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'reports' ? '' : ' d-none' ?>" id="section-reports">
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Raporlar</h2>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="date" id="reportStart" class="form-control form-control-sm" value="<?= htmlspecialchars($reportStart) ?>">
                        <input type="date" id="reportEnd" class="form-control form-control-sm" value="<?= htmlspecialchars($reportEnd) ?>">
                        <button class="btn btn-sm btn-primary" id="exportPdf">PDF</button>
                        <button class="btn btn-sm btn-success" id="exportExcel">Excel</button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="reportsTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>

        <section class="section<?= $currentSection === 'settings' ? '' : ' d-none' ?>" id="section-settings">
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Genel Ayarlar</h2>
                </div>
                <div class="card-body">
                    <form id="generalSettingsForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Restoran Adı</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($restaurant['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($restaurant['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($restaurant['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Adres</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($restaurant['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Para Birimi</label>
                            <select name="currency" id="currencySelectAdmin" class="form-select" data-default="<?= htmlspecialchars($restaurant['currency'] ?? 'TRY') ?>">
                                <?php if (empty($currencies)): ?>
                                    <option value="<?= htmlspecialchars($restaurant['currency'] ?? 'TRY') ?>" selected><?= htmlspecialchars($restaurant['currency'] ?? 'TRY') ?></option>
                                <?php else: ?>
                                    <?php foreach ($currencies as $currency): ?>
                                        <option value="<?= htmlspecialchars($currency['code']) ?>" <?= ($currency['code'] ?? '') === ($restaurant['currency'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($currency['code']) ?> &mdash; <?= htmlspecialchars($currency['name'] ?? $currency['code']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Saat Dilimi</label>
                            <select name="timezone" id="timezoneSelect" class="form-select">
                                <?php foreach ($timezones as $timezone): ?>
                                    <option value="<?= htmlspecialchars($timezone) ?>" <?= $timezone === $selectedTimezone ? 'selected' : '' ?>><?= htmlspecialchars($timezone) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Varsayılan Dil</label>
                            <select name="language" id="defaultLanguage" class="form-select"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tema Rengi</label>
                            <input type="color" name="theme_color" class="form-control form-control-color" value="<?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Marka Görselleri</h2>
                </div>
                <div class="card-body">
                    <form id="brandingForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Logo</label>
                            <input type="text" name="logo" id="logoInput" class="form-control" value="<?= htmlspecialchars($branding['logo'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#logoInput" data-preview="#logoPreview" data-placeholder="Logo yüklemek için bırakın"></div>
                            <div class="preview-box">
                                <img src="<?= htmlspecialchars($branding['logo'] ?? '') ?>" alt="Logo" id="logoPreview">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Favicon</label>
                            <input type="text" name="favicon" id="faviconInput" class="form-control" value="<?= htmlspecialchars($branding['favicon'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#faviconInput" data-preview="#faviconPreview" data-placeholder="Favicon yükleyin"></div>
                            <div class="preview-box">
                                <img src="<?= htmlspecialchars($branding['favicon'] ?? '') ?>" alt="Favicon" id="faviconPreview">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">QR Logo</label>
                            <input type="text" name="qr_logo" id="qrLogoInput" class="form-control" value="<?= htmlspecialchars($branding['qr_logo'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#qrLogoInput" data-preview="#qrLogoPreview" data-placeholder="QR logo yükleyin" data-absolute="true"></div>
                            <div class="preview-box">
                                <img src="<?= htmlspecialchars($branding['qr_logo'] ?? '') ?>" alt="QR Logo" id="qrLogoPreview">
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">QR Kod Ayarları</h2>
                </div>
                <div class="card-body">
                    <form id="qrForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">API Token</label>
                            <input type="text" name="token" class="form-control" value="<?= htmlspecialchars($qrSettings['token'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Genişlik</label>
                            <input type="number" name="width" class="form-control" value="<?= htmlspecialchars($qrSettings['width'] ?? 400) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yükseklik</label>
                            <input type="number" name="height" class="form-control" value="<?= htmlspecialchars($qrSettings['height'] ?? 400) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <?php foreach (['png', 'svg', 'jpg'] as $format): ?>
                                    <option value="<?= $format ?>" <?= (($qrSettings['format'] ?? 'png') === $format) ? 'selected' : '' ?>><?= strtoupper($format) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Şeffaf Arkaplan</label>
                            <select name="transparent" class="form-select">
                                <option value="true" <?= !empty($qrSettings['transparent']) ? 'selected' : '' ?>>Evet</option>
                                <option value="false" <?= empty($qrSettings['transparent']) ? 'selected' : '' ?>>Hayır</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">QR Renk</label>
                            <input type="color" name="color" class="form-control form-control-color" value="<?= htmlspecialchars($qrSettings['color'] ?? '#000000') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Arkaplan Renk</label>
                            <input type="color" name="background" class="form-control form-control-color" value="<?= htmlspecialchars($qrSettings['background'] ?? '#ffffff') ?>">
                        </div>
                        <div class="col-md-12 d-flex flex-column flex-md-row align-items-md-center gap-3">
                            <img src="<?= htmlspecialchars($qrPreview) ?>" alt="QR Önizleme" class="qr-preview" id="qrPreviewImage">
                            <div>
                                <p class="mb-1 text-muted">Aşağıdaki örnek QR kodu yüklenen logo ile güncellenir.</p>
                                <small class="text-muted">Base URL: <?= htmlspecialchars(BASE_URL) ?></small>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Bildirim Sesleri</h2>
                </div>
                <div class="card-body">
                    <form id="notificationsForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Sipariş Bildirim Sesi</label>
                            <input type="text" name="order_sound" id="orderSoundInput" class="form-control" value="<?= htmlspecialchars($notifications['order_sound'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#orderSoundInput" data-audio="#orderSoundPreview" data-accept="audio/*" data-placeholder="Ses dosyasını buraya bırakın"></div>
                            <audio id="orderSoundPreview" class="w-100 mt-2" controls src="<?= htmlspecialchars($orderSound) ?>"></audio>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Garson Bildirim Sesi</label>
                            <input type="text" name="waiter_sound" id="waiterSoundInput" class="form-control" value="<?= htmlspecialchars($notifications['waiter_sound'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#waiterSoundInput" data-audio="#waiterSoundPreview" data-accept="audio/*" data-placeholder="Ses dosyasını buraya bırakın"></div>
                            <audio id="waiterSoundPreview" class="w-100 mt-2" controls src="<?= htmlspecialchars($waiterSound) ?>"></audio>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="flash_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="flashToggle" name="flash_enabled" value="1" <?= !empty($notifications['flash_enabled']) ? 'checked' : '' ?> aria-checked="<?= !empty($notifications['flash_enabled']) ? 'true' : 'false' ?>">
                                <label class="form-check-label fw-semibold" for="flashToggle">Tam ekran flaş bildirimi</label>
                                <small class="text-muted d-block mt-1">Yeni sipariş veya garson çağrısı geldiğinde ekran uyarısı göster.</small>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">PHP Mail Ayarları</h2>
                </div>
                <div class="card-body">
                    <form id="mailSettingsForm" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Gönderen Adı</label>
                            <input type="text" name="from_name" class="form-control" value="<?= htmlspecialchars($mailSettings['from_name'] ?? ($restaurant['name'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gönderen E-posta</label>
                            <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($mailSettings['from_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bildirim E-postası</label>
                            <input type="email" name="notification_email" class="form-control" value="<?= htmlspecialchars($mailSettings['notification_email'] ?? ($user['email'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Varsayılan Yanıt Adresi</label>
                            <input type="email" name="reply_to" class="form-control" value="<?= htmlspecialchars($mailSettings['reply_to'] ?? ($mailSettings['notification_email'] ?? ($user['email'] ?? ''))) ?>">
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0">Yönetici Hesabı</h2>
                </div>
                <div class="card-body">
                    <form id="accountForm" class="row g-3 mb-4">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)($user['id'] ?? '')) ?>">
                        <div class="col-md-6">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success" type="submit">Bilgileri Güncelle</button>
                        </div>
                    </form>
                    <hr>
                    <form id="passwordForm" class="row g-3">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars((string)($user['id'] ?? '')) ?>">
                        <div class="col-md-4">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-outline-primary" type="submit">Şifreyi Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Dil Yönetimi</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#languageModal">Yeni Dil</button>
                </div>
                <div class="card-body">
                    <table id="languagesTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Para Birimleri</h2>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#currencyModal">Para Birimi Ekle</button>
                </div>
                <div class="card-body">
                    <div class="currency-badges mb-3" id="currencyBadges">
                        <?php foreach ($currencies as $currency): ?>
                            <span class="badge rounded-pill <?= !empty($currency['is_default']) ? 'bg-success' : 'bg-secondary' ?> me-2 mb-2">
                                <?= htmlspecialchars($currency['code']) ?> &mdash; <?= htmlspecialchars($currency['name'] ?? $currency['code']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <table id="currenciesTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal fade" id="tableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Masa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="tableForm" class="row g-3">
                    <input type="hidden" name="id">
                    <div class="col-12">
                        <label class="form-label">Masa Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Durum</label>
                        <select name="status" class="form-select">
                            <option value="available">Boş</option>
                            <option value="occupied">Dolu</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="deleteTableButton" data-id="0">Sil</button>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-success" id="saveTable">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" class="row g-3">
                    <input type="hidden" name="id">
                    <div class="col-12">
                        <label class="form-label">Kategori Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Icon Seçimi</label>
                        <input type="hidden" name="icon" id="categoryIconInput">
                        <div id="iconLibrary" class="icon-library"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Kategori Görseli</label>
                        <input type="text" name="image" id="categoryImageInput" class="form-control" readonly>
                        <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#categoryImageInput" data-placeholder="Kategori görseli yükleyin"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="deleteCategoryButton" data-id="0">Sil</button>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-success" id="saveCategory">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dailyMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Günün Menüsü</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="dailyMenuForm" class="row g-3">
                    <input type="hidden" name="id">
                    <div class="col-12">
                        <label class="form-label">Ürün</label>
                        <select name="product_id" id="dailyMenuProduct" class="form-select" required></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Başlık</label>
                        <input type="text" name="headline" class="form-control" placeholder="Örn. Günün içeceği">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alt Başlık</label>
                        <input type="text" name="tagline" class="form-control" placeholder="Kısa açıklama">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Rozet</label>
                        <input type="text" name="badge" class="form-control" placeholder="Örn. Yeni">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" id="saveDailyMenu">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ürün</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm" class="row g-3">
                    <input type="hidden" name="id">
                    <div class="col-md-6">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ürün Adı</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ana Fiyat (Varsayılan)</label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Ürün Görseli</label>
                        <input type="text" name="image" id="productImageInput" class="form-control" readonly>
                        <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#productImageInput" data-placeholder="Ürün görseli yükleyin"></div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Varyasyonlar</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addVariant">Varyasyon Ekle</button>
                        </div>
                        <div id="variantList" class="variant-list"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="deleteProductButton" data-id="0">Sil</button>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="button" class="btn btn-success" id="saveProduct">Kaydet</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sipariş Detayı</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderModalContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="languageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dil Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="languageForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Dil Kodu</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Görünen Ad</label>
                        <input type="text" name="label" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Çeviriler (JSON)</label>
                        <textarea name="translations" class="form-control" rows="12" placeholder='{"app":{"title":""}}' required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" id="saveLanguage">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="currencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Para Birimi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="currencyForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control" placeholder="TRY" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sembol</label>
                        <input type="text" name="symbol" class="form-control" placeholder="₺" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Varsayılan</label>
                        <select name="is_default" class="form-select">
                            <option value="0">Hayır</option>
                            <option value="1">Evet</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" placeholder="Türk Lirası" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-success" id="saveCurrency">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<audio id="audioOrderAdmin" preload="auto" src="<?= htmlspecialchars($orderSound) ?>"></audio>
<audio id="audioNotifyAdmin" preload="auto" src="<?= htmlspecialchars($waiterSound) ?>"></audio>

<script>
    window.APP_BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE) ?>;
    window.APP_USER = <?= json_encode($user, JSON_UNESCAPED_UNICODE) ?>;
    window.APP_STATE = {
        settings: <?= json_encode($settings, JSON_UNESCAPED_UNICODE) ?>,
        qrPreview: <?= json_encode($qrPreview, JSON_UNESCAPED_UNICODE) ?>,
        currentSection: <?= json_encode($currentSection, JSON_UNESCAPED_UNICODE) ?>,
        sectionPaths: <?= json_encode($sectionPaths, JSON_UNESCAPED_UNICODE) ?>,
        notifications: <?= json_encode($notifications, JSON_UNESCAPED_UNICODE) ?>,
        timezones: <?= json_encode($timezones, JSON_UNESCAPED_UNICODE) ?>,
        baseUrl: <?= json_encode($baseUrl, JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= htmlspecialchars($asset('assets/js/admin.js')) ?>"></script>
</body>
</html>
