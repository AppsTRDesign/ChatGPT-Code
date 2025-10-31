<?php
require_once __DIR__ . '/bootstrap.php';

use Helpers\Language;
use App\Services\SettingsService;

$settingsService = new SettingsService();
$settings = $settingsService->all();
$defaultLanguage = $settings['restaurant']['language'] ?? 'tr';
Language::load($defaultLanguage);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($defaultLanguage) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(Language::get('app.title')) ?></title>
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
<div class="wrapper">
    <aside class="sidebar">
        <h1><?= htmlspecialchars($settings['restaurant']['name'] ?? 'Restaurant') ?></h1>
        <nav>
            <a href="#" class="active"><?= htmlspecialchars(Language::get('app.dashboard')) ?></a>
            <a href="#orders"><?= htmlspecialchars(Language::get('app.orders')) ?></a>
            <a href="#tables"><?= htmlspecialchars(Language::get('app.tables')) ?></a>
            <a href="#reports"><?= htmlspecialchars(Language::get('app.reports')) ?></a>
            <a href="#settings"><?= htmlspecialchars(Language::get('app.settings')) ?></a>
        </nav>
    </aside>
    <main class="content">
        <section class="cards">
            <article class="card" data-summary="total_orders">
                <h3><?= htmlspecialchars(Language::get('dashboard.total_orders')) ?></h3>
                <strong>0</strong>
            </article>
            <article class="card" data-summary="revenue">
                <h3><?= htmlspecialchars(Language::get('dashboard.revenue')) ?></h3>
                <strong>0</strong>
            </article>
            <article class="card" data-summary="active_tables">
                <h3><?= htmlspecialchars(Language::get('dashboard.active_tables')) ?></h3>
                <strong>0</strong>
            </article>
            <article class="card" data-summary="waiter_calls">
                <h3><?= htmlspecialchars(Language::get('dashboard.waiter_calls')) ?></h3>
                <strong>0</strong>
            </article>
        </section>

        <section class="table-container" id="orders">
            <h2><?= htmlspecialchars(Language::get('app.orders')) ?></h2>
            <div style="height:320px;margin-bottom:24px;">
                <canvas id="ordersChart"></canvas>
            </div>
            <table id="ordersTable" class="table table-striped" style="width:100%"></table>
        </section>

        <section class="table-container" id="tables">
            <h2><?= htmlspecialchars(Language::get('app.tables')) ?></h2>
            <table id="tablesTable" class="table table-striped" style="width:100%"></table>
        </section>

        <section class="table-container" id="waiter">
            <h2><?= htmlspecialchars(Language::get('dashboard.waiter_calls')) ?></h2>
            <table id="waiterTable" class="table table-striped" style="width:100%"></table>
        </section>

        <section class="table-container" id="settings">
            <h2><?= htmlspecialchars(Language::get('app.settings')) ?></h2>
            <form id="settingsForm" class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Restaurant Adı</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($settings['restaurant']['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['restaurant']['phone'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Adres</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings['restaurant']['address'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Para Birimi</label>
                    <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($settings['restaurant']['currency'] ?? 'TRY') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Saat Dilimi</label>
                    <input type="text" name="timezone" class="form-control" value="<?= htmlspecialchars($settings['restaurant']['timezone'] ?? 'Europe/Istanbul') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Varsayılan Dil</label>
                    <select id="defaultLanguage" name="language" class="form-select"></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tema Rengi</label>
                    <input type="color" name="theme_color" class="form-control form-control-color" value="<?= htmlspecialchars($settings['restaurant']['theme_color'] ?? '#0f9d58') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">QR Token</label>
                    <input type="text" name="qr_token" class="form-control" value="<?= htmlspecialchars($settings['qr']['token'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">QR Genişlik</label>
                    <input type="number" name="qr_width" class="form-control" value="<?= htmlspecialchars($settings['qr']['width'] ?? 400) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">QR Yükseklik</label>
                    <input type="number" name="qr_height" class="form-control" value="<?= htmlspecialchars($settings['qr']['height'] ?? 400) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">QR Format</label>
                    <select name="qr_format" class="form-select">
                        <?php foreach (['png', 'svg', 'jpg'] as $format): ?>
                        <option value="<?= $format ?>" <?= (($settings['qr']['format'] ?? 'png') === $format) ? 'selected' : '' ?>><?= strtoupper($format) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">QR Şeffaf</label>
                    <select name="qr_transparent" class="form-select">
                        <option value="true" <?= !empty($settings['qr']['transparent']) ? 'selected' : '' ?>>True</option>
                        <option value="false" <?= empty($settings['qr']['transparent']) ? 'selected' : '' ?>>False</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">QR Renk</label>
                    <input type="color" name="qr_color" class="form-control form-control-color" value="<?= htmlspecialchars($settings['qr']['color'] ?? '#000000') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">QR Arkaplan</label>
                    <input type="color" name="qr_background" class="form-control form-control-color" value="<?= htmlspecialchars($settings['qr']['background'] ?? '#ffffff') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">QR Logo</label>
                    <input type="text" name="qr_logo" id="qrLogoInput" class="form-control" value="<?= htmlspecialchars($settings['qr']['logo'] ?? '') ?>" readonly>
                    <div class="dropzone mt-2" data-dropzone data-target="#qrLogoInput" data-placeholder="QR logo yüklemek için dosyayı bırakın"></div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success px-4 py-2">Kaydet</button>
                </div>
            </form>
        </section>
    </main>
</div>
<script src="assets/js/admin.js"></script>
</body>
</html>
