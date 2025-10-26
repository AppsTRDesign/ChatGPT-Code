<?php
require_once __DIR__ . '/../config.php';
$settings = fetch_settings($pdo);
$title = $settings['meta_title'] ?? 'NoaSoft Dosya Deposu';
$description = $settings['meta_description'] ?? 'Dosyalarınızı güvenle saklayın ve paylaşın.';
$logoPath = !empty($settings['logo']) ? BASE_URL . '/uploads/' . ltrim($settings['logo'], '/') : BASE_URL . '/assets/img/logo.svg';
$faviconPath = !empty($settings['favicon']) ? BASE_URL . '/uploads/' . ltrim($settings['favicon'], '/') : BASE_URL . '/assets/img/favicon.svg';
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= sanitize($description) ?>">
    <title><?= sanitize($title) ?></title>
    <link rel="icon" href="<?= sanitize($faviconPath) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=1.2.0">
    <?php if (!empty($settings['analytics_enabled']) && !empty($settings['analytics_code'])): ?>
        <?= $settings['analytics_code'] ?>
    <?php endif; ?>
    <?= $settings['header_html'] ?? '' ?>
    <script>
        window.APP_CONFIG = Object.assign({}, window.APP_CONFIG || {}, {
            baseUrl: '<?= BASE_URL ?>',
            csrfToken: '<?= csrf_token() ?>'
        });
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
            <img src="<?= sanitize($logoPath) ?>" alt="Logo" width="36" height="36" class="rounded-circle bg-white p-1">
            <span class="fw-bold">NoaSoft Depo</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>#ozellikler">Özellikler</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/contact">İletişim</a></li>
                <?php if (!current_user()): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login">Giriş Yap</a></li>
                    <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="<?= BASE_URL ?>/register">Üye Ol</a></li>
                <?php else: ?>
                    <?php if (is_admin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin">Yönetim</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/client">Panelim</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="btn btn-outline-light ms-lg-2" href="<?= BASE_URL ?>/logout">Çıkış Yap</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="page-wrapper">
