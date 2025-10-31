<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;
use App\Services\AuthService;
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
$defaultLanguage = $restaurant['language'] ?? 'tr';
Language::load($defaultLanguage);

$qrSettings = $settings['qr'] ?? [];
$qrPreview = Config::get('qr_api')['base_url'] . '?' . http_build_query([
    'token' => $qrSettings['token'] ?? '',
    'type' => 'url',
    'url' => BASE_URL . '/menu.php',
    'width' => $qrSettings['width'] ?? 400,
    'height' => $qrSettings['height'] ?? 400,
    'color' => $qrSettings['color'] ?? '#000000',
    'background' => $qrSettings['background'] ?? '#ffffff',
    'format' => $qrSettings['format'] ?? 'png',
    'background_transparent' => !empty($qrSettings['transparent']) ? 'true' : 'false',
]);

$reportStart = (new DateTimeImmutable('-6 days'))->format('Y-m-d');
$reportEnd = (new DateTimeImmutable('now'))->format('Y-m-d');
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
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.0.3/r-3.0.1/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://qrmenu.noasoft.org:4000/socket.io/socket.io.js"></script>
</head>
<body>
<div class="dashboard" data-theme-color="<?= htmlspecialchars($restaurant['theme_color'] ?? '#0f9d58') ?>">
    <aside class="dashboard__sidebar">
        <div class="dashboard__brand">
            <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="<?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?>" class="dashboard__logo">
            <?php else: ?>
                <h1><?= htmlspecialchars($restaurant['name'] ?? 'Restoran') ?></h1>
            <?php endif; ?>
        </div>
        <nav class="dashboard__nav">
            <button type="button" class="dashboard__link active" data-section="dashboard">
                <?= htmlspecialchars(Language::get('app.dashboard')) ?>
            </button>
            <button type="button" class="dashboard__link" data-section="orders">
                <?= htmlspecialchars(Language::get('app.orders')) ?>
            </button>
            <button type="button" class="dashboard__link" data-section="tables">
                <?= htmlspecialchars(Language::get('app.tables')) ?>
            </button>
            <button type="button" class="dashboard__link" data-section="waiter">
                <?= htmlspecialchars(Language::get('dashboard.waiter_calls')) ?>
            </button>
            <button type="button" class="dashboard__link" data-section="reports">
                <?= htmlspecialchars(Language::get('app.reports')) ?>
            </button>
            <button type="button" class="dashboard__link" data-section="settings">
                <?= htmlspecialchars(Language::get('app.settings')) ?>
            </button>
        </nav>
        <div class="dashboard__user">
            <div>
                <span class="dashboard__user-name"><?= htmlspecialchars($user['name']) ?></span>
                <small class="d-block text-muted"><?= htmlspecialchars($user['email']) ?></small>
            </div>
            <button class="btn btn-sm btn-outline-light" id="logoutButton">Çıkış</button>
        </div>
    </aside>
    <main class="dashboard__content">
        <section class="section" id="section-dashboard">
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
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><?= htmlspecialchars(Language::get('dashboard.orders_chart')) ?></span>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" data-report="daily">Günlük</button>
                        <button class="btn btn-sm btn-outline-primary" data-report="weekly">Haftalık</button>
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

        <section class="section d-none" id="section-orders">
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Gelen Siparişler</h2>
                    <div class="btn-group" id="orderStatusFilters">
                        <button class="btn btn-sm btn-outline-secondary active" data-status="all">Tümü</button>
                        <button class="btn btn-sm btn-outline-secondary" data-status="Beklemede">Beklemede</button>
                        <button class="btn btn-sm btn-outline-secondary" data-status="Hazırlanıyor">Hazırlanıyor</button>
                        <button class="btn btn-sm btn-outline-secondary" data-status="Hazırlandı">Hazırlandı</button>
                        <button class="btn btn-sm btn-outline-secondary" data-status="Tamamlandı">Tamamlandı</button>
                        <button class="btn btn-sm btn-outline-secondary" data-status="İptal">İptal</button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="ordersTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>

        <section class="section d-none" id="section-tables">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h2 class="h5 mb-0">Masalar ve QR Kodlar</h2>
                    <button class="btn btn-sm btn-primary" id="newTableButton">Yeni Masa</button>
                </div>
                <div class="card-body">
                    <table id="tablesTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>

        <section class="section d-none" id="section-waiter">
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0">Garson Çağrıları</h2>
                </div>
                <div class="card-body">
                    <table id="waiterTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>

        <section class="section d-none" id="section-reports">
            <div class="card">
                <div class="card-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Raporlar</h2>
                    <div class="d-flex gap-2">
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

        <section class="section d-none" id="section-settings">
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
                            <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($restaurant['currency'] ?? 'TRY') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Saat Dilimi</label>
                            <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($restaurant['timezone'] ?? 'Europe/Istanbul') ?>">
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
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#logoInput" data-placeholder="Logo yüklemek için dosyayı bırakın"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Favicon</label>
                            <input type="text" name="favicon" id="faviconInput" class="form-control" value="<?= htmlspecialchars($branding['favicon'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#faviconInput" data-placeholder="Favicon yüklemek için dosyayı bırakın"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">QR Logo</label>
                            <input type="text" name="qr_logo" id="qrLogoInput" class="form-control" value="<?= htmlspecialchars($branding['qr_logo'] ?? '') ?>" readonly>
                            <div class="dropzone mt-2 dz-dashed" data-dropzone data-target="#qrLogoInput" data-placeholder="QR logo yüklemek için dosyayı bırakın"></div>
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
                                <p class="mb-1 text-muted">Örnek QR kodunuz oluşturulan masa bağlantılarını otomatik gösterecektir.</p>
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
                    <table id="currenciesTable" class="table table-striped table-hover w-100"></table>
                </div>
            </div>
        </section>
    </main>
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

<script>
    window.APP_STATE = {
        settings: <?= json_encode($settings, JSON_UNESCAPED_UNICODE) ?>,
        qrPreview: <?= json_encode($qrPreview, JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="assets/js/admin.js"></script>
</body>
</html>
